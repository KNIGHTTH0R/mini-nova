<?php

namespace Mini\Pipeline;

use Mini\Container\Container;
use Mini\Pipeline\Contracts\PipelineInterface;

use Closure;


class Pipeline implements PipelineInterface
{
    /**
     * The container implementation.
     *
     * @var \Mini\Container\Container
     */
    protected $container;

    /**
     * The object being passed through the pipeline.
     *
     * @var mixed
     */
    protected $passable;

    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected $pipes = array();

    /**
     * The method to call on each pipe.
     *
     * @var string
     */
    protected $method = 'handle';


    /**
     * Create a new class instance.
     *
     * @param  \Mini\Container\Container  $container
     * @return void
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Set the object being sent through the pipeline.
     *
     * @param  mixed  $passable
     * @return $this
     */
    public function send($passable)
    {
        $this->passable = $passable;

        return $this;
    }

    /**
     * Set the array of pipes.
     *
     * @param  array|mixed  $pipes
     * @return $this
     */
    public function through($pipes)
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();

        return $this;
    }

    /**
     * Set the method to call on the pipes.
     *
     * @param  string  $method
     * @return $this
     */
    public function via($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Run the pipeline with a final destination callback.
     *
     * @param  \Closure  $destination
     * @return mixed
     */
    public function then(Closure $destination)
    {
        $firstSlice = $this->getInitialSlice($destination);

        $pipes = array_reverse($this->pipes);

        //
        $callback = array_reduce($pipes, $this->getSlice(), $firstSlice);

        return call_user_func($callback, $this->passable);
    }

    /**
     * Get a Closure that represents a slice of the application onion.
     *
     * @return \Closure
     */
    protected function getSlice()
    {
        return function ($stack, $pipe)
        {
            return function ($passable) use ($stack, $pipe)
            {
                if ($pipe instanceof Closure) {
                    return call_user_func($pipe, $passable, $stack);
                } else if (is_array($pipe)) {
                    list($callback, $parameters) = array_values($pipe);

                    if (is_string($parameters)) {
                        $parameters = explode(',', $parameters);
                    } else {
                        $parameters = $parameters ?: array();
                    }

                    $parameters = array_merge(array($passable, $stack), $parameters);

                    return call_user_func_array($callback, $parameters);
                } else {
                    list($name, $parameters) = $this->parsePipeString($pipe);

                    $parameters = array_merge(array($passable, $stack), $parameters);

                    $instance = $this->container->make($name);

                    return call_user_func_array(array($instance, $this->method), $parameters);
                }
            };
        };
    }

    /**
     * Get the initial slice to begin the stack call.
     *
     * @param  \Closure  $destination
     * @return \Closure
     */
    protected function getInitialSlice(Closure $destination)
    {
        return function ($passable) use ($destination)
        {
            return call_user_func($destination, $passable);
        };
    }

    /**
     * Parse full pipe string to get name and parameters.
     *
     * @param  string $pipe
     * @return array
     */
    protected function parsePipeString($pipe)
    {
        list($name, $parameters) = array_pad(explode(':', $pipe, 2), 2, array());

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return array($name, $parameters);
    }
}
