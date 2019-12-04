<?php

namespace App\Notifications;

use App\Models\SuppressionList;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ListReadyNotification extends Notification // implements ShouldQueue
{
    // use Queueable;

    public $data;

    /** @var int */
    protected $suppressionListId;

    protected $suppressionList;

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
        return ['mail', 'database', 'broadcast'];
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
        return (new MailMessage)->markdown('mail.list.ready', $this->toArray($notifiable));
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
            'message'  => __('Your suppression list is ready to use.'),
            'id'       => $this->suppressionList()->id,
            'name'     => $this->suppressionList()->name,
            'url'      => route('suppressionList', ['id' => $this->suppressionList()->id]),
            'userId'   => $this->suppressionList()->user->id,
            'userName' => $this->suppressionList()->user->name,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    private function suppressionList()
    {
        if (!$this->suppressionList) {
            $this->suppressionList = SuppressionList::query()->findOrFail($this->suppressionListId);
        }

        return $this->suppressionList;
    }

    /**
     * @param $notifiable
     *
     * @return BroadcastMessage
     */
    public function toBroadcast($notifiable)
    {
        $message        = new BroadcastMessage($this->toArray($notifiable));
        $message->queue = $this->queue;

        return $message;
    }
}
