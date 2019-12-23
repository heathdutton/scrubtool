<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class NotificationAbstract extends Notification
{

    /** @var array */
    public $data;

    /** @var string */
    public $queue;

    /** @var string */
    public $markdown;

    /** @var array */
    public $quiet = false;

    /** @var object */
    protected $object;

    /**
     * AbstractNotification constructor.
     *
     * @param $object
     * @param  null  $markdown
     */
    public function __construct($object, $markdown = null)
    {
        $this->object = $object;
        if ($markdown) {
            $this->markdown = $markdown;
        }
        $this->queue = 'notify';
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
        $methods = [];
        if (!empty($notifiable->email) && !$this->quiet) {
            $methods[] = 'mail';
        }
        if ($notifiable instanceof User) {
            $methods[] = 'database';
            $methods[] = 'broadcast';
        }

        return $methods;
    }

    /**
     * @param $notifiable
     *
     * @return MailMessage
     */
    public function toMail($notifiable)
    {
        $message = new MailMessage();
        $data    = $this->toArray($notifiable);
        if (!empty($data['title'])) {
            $message->subject($data['title']);
        }
        if ($this->markdown) {
            $message->markdown($this->markdown, $data);
        }

        return $message;
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
