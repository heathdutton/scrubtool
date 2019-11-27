<?php

namespace App\Notifications;

use App\Models\SuppressionList;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\PusherPushNotifications\PusherChannel;
use NotificationChannels\PusherPushNotifications\PusherMessage;

class ListReady extends Notification implements ShouldQueue
{
    use Queueable;

    public $timeout = 60;

    /** @var int */
    protected $suppressionListId;

    /**
     * ListReady constructor.
     *
     * @param  int  $suppressionListId
     */
    public function __construct($suppressionListId)
    {
        $this->suppressionListId = $suppressionListId;
        $this->queue             = 'notify';
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     *
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database', PusherChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     *
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)->markdown('mail.list.ready');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     *
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

    public function toPushNotification($notifiable)
    {
        $suppressionList = SuppressionList::query()->findOrFail($this->suppressionListId);

        return PusherMessage::create()
            ->badge(1)
            ->sound('success')
            ->body('Suppression list '.$suppressionList->name.' is ready for you.');
    }
}
