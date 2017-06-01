<?php

namespace Notifications;

use Mini\Events\Dispatcher;
use Mini\Foundation\Application;
use Mini\Support\Collection;
use Mini\Support\Manager;

use Notifications\Channels\DatabaseChannel;
use Notifications\Channels\MailChannel;
use Notifications\Contracts\DispatcherInterface;
use Notifications\Events\NotificationSending;
use Notifications\Events\NotificationSent;

use Ramsey\Uuid\Uuid;

use InvalidArgumentException;


class ChannelManager extends Manager implements DispatcherInterface
{
	/**
	 * The events dispatcher instance.
	 *
	 * @var \Mini\Events\Dispatcher
	 */
	protected $events;

	/**
	 * The default channels used to deliver messages.
	 *
	 * @var array
	 */
	protected $defaultChannel = 'mail';


	/**
	 * Create a new manager instance.
	 *
	 * @param  \Mini\Foundation\Application  $app
	 * @return void
	 */
	public function __construct(Application $app, Dispatcher $events)
	{
		$this->app = $app;

		$this->events = $events;
	}

	/**
	 * Send the given notification to the given notifiable entities.
	 *
	 * @param  \Mini\Support\Collection|array|mixed  $notifiables
	 * @param  mixed  $notification
	 * @param  array|null  $channels
	 * @return void
	 */
	public function send($notifiables, $notification, array $channels = null)
	{
		if ((! $notifiables instanceof Collection) && ! is_array($notifiables)) {
			$notifiables = array($notifiables);
		}

		$original = clone $notification;

		foreach ($notifiables as $notifiable) {
			$notificationId = Uuid::uuid4()->toString();

			$channels = $channels ?: $notification->via($notifiable);

			if (empty($channels)) {
				continue;
			}

			foreach ($channels as $channel) {
				$notification = clone $original;

				if (is_null($notification->id)) {
					$notification->id = $notificationId;
				}

				if (! $this->shouldSendNotification($notifiable, $notification, $channel)) {
					continue;
				}

				$response = $this->driver($channel)->send($notifiable, $notification);

				$this->events->fire(
					new NotificationSent($notifiable, $notification, $channel, $response)
				);
			}
		}
	}

	/**
	 * Determines if the notification can be sent.
	 *
	 * @param  mixed  $notifiable
	 * @param  mixed  $notification
	 * @param  string  $channel
	 * @return bool
	 */
	protected function shouldSendNotification($notifiable, $notification, $channel)
	{
		$result = $this->events->until(
			new NotificationSending($notifiable, $notification, $channel)
		);

		return ($result !== false);
	}

	/**
	 * Get a channel instance.
	 *
	 * @param  string|null  $name
	 * @return mixed
	 */
	public function channel($name = null)
	{
		return $this->driver($name);
	}

	/**
	 * Create an instance of the database driver.
	 *
	 * @return \Notifications\Channels\DatabaseChannel
	 */
	protected function createDatabaseDriver()
	{
		return $this->app->make(DatabaseChannel::class);
	}

	/**
	 * Create an instance of the mail driver.
	 *
	 * @return \Notifications\Channels\MailChannel
	 */
	protected function createMailDriver()
	{
		return $this->app->make(MailChannel::class);
	}

	/**
	 * Create a new driver instance.
	 *
	 * @param  string  $driver
	 * @return mixed
	 *
	 * @throws \InvalidArgumentException
	 */
	protected function createDriver($driver)
	{
		try {
			return parent::createDriver($driver);
		}
		catch (InvalidArgumentException $e) {
			if (class_exists($driver)) {
				return $this->app->make($driver);
			}

			throw $e;
		}
	}

	/**
	 * Get the default channel driver name.
	 *
	 * @return string
	 */
	public function getDefaultDriver()
	{
		return $this->defaultChannel;
	}

	/**
	 * Get the default channel driver name.
	 *
	 * @return string
	 */
	public function deliversVia()
	{
		return $this->defaultChannel;
	}

	/**
	 * Set the default channel driver name.
	 *
	 * @param  string  $channel
	 * @return void
	 */
	public function deliverVia($channel)
	{
		$this->defaultChannel = $channel;
	}
}
