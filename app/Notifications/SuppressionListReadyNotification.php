<?php

namespace App\Notifications;

class SuppressionListReadyNotification extends NotificationAbstract
{

    /** @var string */
    public $markdown = 'mail.default';

    /**
     * Tightly control the data for this notification with toArray.
     *
     * @param  mixed  $notifiable
     *
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'title'    => __('Your Suppression List is Ready to Use'),
            'message'  => __('Suppression list :suppressionList is ready.',
                ['suppressionList' => $this->object->name]),
            'icon'     => 'list',
            'url'      => route('suppressionList', ['id' => $this->object->id]),
            'action'   => __('Open Suppression List'),
            'userId'   => $this->object->user->id ?? '',
            'userName' => $this->object->user->name ?? '',
            'close'    => __('Thanks'),
        ];
    }

}
