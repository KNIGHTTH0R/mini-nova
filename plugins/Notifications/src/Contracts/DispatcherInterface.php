<?php

namespace Notifications\Contracts;


interface DispatcherInterface
{
	/**
	 * Send the given notification to the given notifiable entities.
	 *
	 * @param  \Mini\Support\Collection|array|mixed  $notifiables
	 * @param  mixed  $notification
	 * @return void
	 */
	public function send($notifiables, $notification);
}
