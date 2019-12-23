<?php

namespace App\Notifications;

class SuppressionListRestoredNotification extends NotificationAbstract
{

    /** @var string */
    public $markdown = 'mail.default';

    /** @var bool */
    public $quiet = true;

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
            'title'    => __('Your Suppression List has been restored'),
            'message'  => __('Suppression list :suppressionList has been restored.',
                ['suppressionList' => $this->object->name]),
            'icon'     => 'list',
            'url'      => route('suppressionList', ['id' => $this->object->id]),
            'action'   => __('Restore Suppression List'),
            'userId'   => $this->object->user->id ?? '',
            'userName' => $this->object->user->name ?? '',
            'close'    => __('Thanks'),
        ];
    }

}
