<?php

namespace Notifications\Channels;

use Mini\Mail\Mailer;
use Mini\Support\Str;

use Notifications\Notification;


class MailChannel
{
	/**
	 * The mailer implementation.
	 *
	 * @var \Mini\Mail\Mailer
	 */
	protected $mailer;

	/**
	 * Create a new mail channel instance.
	 *
	 * @param  \Mini\Mail\Mailer  $mailer
	 * @return void
	 */
	public function __construct(Mailer $mailer)
	{
		$this->mailer = $mailer;
	}

	/**
	 * Send the given notification.
	 *
	 * @param  mixed  $notifiable
	 * @param  \Notifications\Notification  $notification
	 * @return void
	 */
	public function send($notifiable, Notification $notification)
	{
		if (is_null($recipients = $notifiable->routeNotificationFor('mail'))) {
			return;
		}

		$mail = $notification->toMail($notifiable);

		$this->mailer->send($mail->view, $mail->data(), function ($message) use ($notification, $recipients, $mail)
		{
			if (is_array($recipients)) {
				$message->bcc($recipients);
			} else {
				$message->to($recipients);
			}

			$message->subject($mail->subject ?: Str::title(
				Str::snake(class_basename($notification), ' ')
			));

			foreach ($mail->attachments as $attachment) {
				$message->attach($attachment['file'], $attachment['options']);
			}

			foreach ($mail->rawAttachments as $attachment) {
				$message->attachData($attachment['data'], $attachment['name'], $attachment['options']);
			}
		});
	}
}
