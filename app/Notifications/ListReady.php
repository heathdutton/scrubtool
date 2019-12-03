<?php

namespace App\Notifications;

use App\Events\Broadcast;
use App\Models\SuppressionList;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ListReady extends Notification implements ShouldBroadcast
{
    use Dispatchable, Queueable;

    public $timeout = 60;

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
        return ['mail', 'broadcast'];
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
            'message' => __('Your suppression list is ready to use.'),
            'name'    => $this->suppressionList()->name,
            'id'      => $this->suppressionList()->id,
            'user'    => $this->suppressionList()->user,
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

    public function broadcastOn()
    {
        return new PrivateChannel('App.Models.User.'.$this->suppressionList()->user->id); // 'App.User.'.$this->userId
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @param  mixed  $notifiable
     *
     * @return BroadcastMessage
     */
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
