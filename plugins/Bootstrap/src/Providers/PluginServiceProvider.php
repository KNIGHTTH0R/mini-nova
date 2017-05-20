<?php

namespace Bootstrap\Providers;

use Mini\Support\ServiceProvider;


class PluginServiceProvider extends ServiceProvider
{

	/**
	 * Bootstrap the Application Events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$path = realpath(__DIR__ .'/../');

		// Configure the Package.
		$this->package('Bootstrap', 'bootstrap', $path);

		//
	}

	/**
	 * Register the Bootstrap plugin Service Provider.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

}
