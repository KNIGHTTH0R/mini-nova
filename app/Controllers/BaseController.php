<?php

namespace App\Controllers;

use Mini\Foundation\Auth\Access\AuthorizesRequestsTrait;
use Mini\Foundation\Bus\DispatchesCommandsTrait;
use Mini\Foundation\Validation\ValidatesRequestsTrait;
use Mini\Http\Response;
use Mini\Routing\Controller;
use Mini\Support\Contracts\RenderableInterface;
use Mini\Support\Facades\App;
use Mini\Support\Facades\Config;
use Mini\Support\Facades\Language;
use Mini\Support\Facades\View;
use Mini\Support\Str;

use BadMethodCallException;


class BaseController extends Controller
{
	use AuthorizesRequestsTrait, ValidatesRequestsTrait, DispatchesCommandsTrait;

	/**
	 * The currently called action.
	 *
	 * @var string
	 */
	protected $action;

	/**
	 * The currently used Theme.
	 *
	 * @var string
	 */
	protected $theme;

	/**
	 * The currently used Layout.
	 *
	 * @var string
	 */
	protected $layout = 'Default';

	/**
	 * True when the auto-rendering is active.
	 *
	 * @var bool
	 */
	protected $autoRender = true;

	/**
	 * True when the auto-layouting is active.
	 *
	 * @var bool
	 */
	protected $autoLayout = true;

	/**
	 * The View path for views of this Controller.
	 *
	 * @var array
	 */
	protected $viewPath;

	/**
	 * The View variables.
	 *
	 * @var array
	 */
	protected $viewData = array();

	/**
	 * The Response instance used alternatively.
	 *
	 * @var \Mini\Http\Response
	 */
	protected $response;


	/**
	 * Method executed before any action.
	 *
	 * @return void
	 */
	protected function initialize()
	{
		// Setup the used Theme to default, if it is not already defined.
		if (is_null($this->theme)) {
			$this->theme = Config::get('app.theme', 'Bootstrap');
		}
	}

	/**
	 * Execute an action on the controller.
	 *
	 * @param string  $method
	 * @param array   $params
	 * @return mixed
	 */
	public function callAction($method, array $parameters)
	{
		$this->action = $method;

		//
		$this->initialize();

		$response = call_user_func_array(array($this, $method), $parameters);

		if (is_null($response) && isset($this->response)) {
			$response = $this->response;
		}

		return $this->processResponse($response);
	}

	/**
	 * Process a Controller action response.
	 *
	 * @param  mixed   $response
	 * @return mixed
	 */
	protected function processResponse($response)
	{
		if (! $this->autoRender()) {
			return $response;
		} else if (is_null($response)) {
			$response = $this->createView();
		}

		if ($this->autoLayout() && ($response instanceof RenderableInterface)) {
			return $this->createLayout()->with('content', $response);
		}

		return $response;
	}

	/**
	 * Create the correct View instance, hands it its data, and uses it to render in a Layout.
	 *
	 * @param string $action Action name to render.
	 * @param string $layout Layout to use.
	 * @return string Full output string of view contents.
	 */
	public function render($view = null, $layout = null)
	{
		$this->autoRender = false;

		if (is_null($view)) {
			$view = $this->getViewName($this->action);
		} else if (Str::startsWith($view, '/')) {
			$view = ltrim($view, '/');
		} else if (! Str::contains($view, '::')) {
			$view = $this->getViewName($view);
		}

		$response = View::make($view, $this->viewData);

		if ($this->autoLayout()) {
			$response = $this->createLayout($layout)->with('content', $response);
		}

		return $this->response = new Response($response);
	}

	/**
	 * Create a View instance for the implicit (or specified) View name.
	 *
	 * @param  array  $data
	 * @param  string|null  $custom
	 * @return \Nova\View\View
	 */
	protected function createView($data = array(), $custom = null)
	{
		$view = $custom ?: ucfirst($this->action);

		return View::make(
			$this->getViewName($view), array_merge($this->viewData, $data)
		);
	}

	/**
	 * Create a View instance for the specified Layout name.
	 *
	 * @param  string|null  $layout
	 * @return \Nova\View\View
	 */
	protected function createLayout($layout = null)
	{
		if (is_null($layout)) {
			$layout = $this->layout;
		}

		$direction = Language::direction();

		if ($direction == 'ltr') {
			$view = $this->getLayoutName($layout);
		} else {
			$view = $this->getLayoutName($layout, true);

			if (! View::exists($view)) {
				$view = $this->getLayoutName($layout);
			}
		}

		return View::make($view, $this->viewData);
	}

	/**
	 * Gets a qualified View name.
	 *
	 * @return string
	 * @throws \BadMethodCallException
	 */
	protected function getViewName($view)
	{
		return $this->getViewPath() .'/' .ucfirst($view);
	}

	/**
	 * Gets a qualified View name for a Layout.
	 *
	 * @param  string|null  $layout
	 * @param  bool  $rtl
	 * @return string
	 */
	protected function getLayoutName($layout = null, $rtl = false)
	{
		if (is_null($layout)) {
			$layout = $this->layout;
		}

		$view = sprintf('Layouts/%s%s', $rtl ? 'RTL/' : '', $layout);

		if (! empty($this->theme)) {
			return $this->theme .'::' .$view;
		}

		return $view;
	}

	/**
	 * Gets a qualified View path.
	 *
	 * @return string
	 * @throws \BadMethodCallException
	 */
	protected function getViewPath()
	{
		if (isset($this->viewPath)) {
			return $this->viewPath;
		}

		$basePath = trim(str_replace('\\', '/', App::getNamespace()), '/');

		$classPath = str_replace('\\', '/', static::class);

		if (preg_match('#^(.+)/Controllers/(.*)$#', $classPath, $matches) === 1) {
			$viewPath = $matches[2];

			//
			$namespace = $matches[1];

			if ($namespace !== $basePath) {
				// A Controller within a Plugin namespace.
				$viewPath = $namespace .'::' .$viewPath;
			}

			return $this->viewPath = $viewPath;
		}

		throw new BadMethodCallException('Invalid controller namespace');
	}

	/**
	 * Add a key / value pair to the view data.
	 *
	 * Bound data will be available to the view as variables.
	 *
	 * @param  string|array  $one
	 * @param  string|array  $two
	 * @return View
	 */
	public function set($one, $two = null)
	{
		if (is_array($one)) {
			$data = is_array($two) ? array_combine($one, $two) : $one;
		} else {
			$data = array($one => $two);
		}

		$this->viewData = $data + $this->viewData;

		return $this;
	}

	/**
	 * Turns on or off Nova's conventional mode of auto-rendering.
	 *
	 * @param bool|null  $enable
	 * @return bool
	 */
	public function autoRender($enable = null)
	{
		if (! is_null($enable)) {
			$this->autoRender = (bool) $enable;

			return $this;
		}

		return $this->autoRender;
	}

	/**
	 * Turns on or off Nova's conventional mode of applying layout files.
	 *
	 * @param bool|null  $enable
	 * @return bool
	 */
	public function autoLayout($enable = null)
	{
		if (! is_null($enable)) {
			$this->autoLayout = (bool) $enable;

			return $this;
		}

		return $this->autoLayout;
	}

	/**
	 * Return the current Theme.
	 *
	 * @return string
	 */
	public function getTheme()
	{
		return $this->theme;
	}

	/**
	 * Return the current Layout.
	 *
	 * @return string
	 */
	public function getLayout()
	{
		return $this->layout;
	}

	/**
	 * Return the current View data.
	 *
	 * @return string
	 */
	public function getViewData()
	{
		return $this->viewData;
	}
}
