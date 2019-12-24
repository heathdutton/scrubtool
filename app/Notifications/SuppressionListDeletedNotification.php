<?php

namespace App\Notifications;

class SuppressionListDeletedNotification extends NotificationAbstract
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
            'title'    => __('Your Suppression List has been deleted'),
            'message'  => __('Suppression list :suppressionList has been deleted. Undo?',
                ['suppressionList' => $this->object->name]),
            'icon'     => 'list',
            'url'      => route('suppressionList.restore', ['id' => $this->object->id]),
            'action'   => __('Restore Suppression List'),
            'userId'   => $this->object->user->id ?? '',
            'userName' => $this->object->user->name ?? '',
            'close'    => __('Thanks'),
        ];
    }

}
