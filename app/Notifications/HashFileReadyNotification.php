<?php

namespace App\Notifications;

class HashFileReadyNotification extends NotificationAbstract
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
            'title'    => __('Your Hashed File is Ready to Download'),
            'message'  => __('The file :file has finished hashing. For the safety of your data the resulting file will be available to download for a limited time.',
                ['file' => $this->object->name]),
            'icon'     => 'files',
            'url'      => route('file.download.with.token',
                [
                    'id'    => $this->object->id,
                    'token' => $this->object->fileDownloadLinks()->create()->token,
                ]),
            'action'   => __('Download File'),
            'userId'   => $this->object->user->id ?? '',
            'userName' => $this->object->user->name ?? '',
            'close'    => __('Thanks'),
        ];
    }

}
