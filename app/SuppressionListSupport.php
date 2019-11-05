<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuppressionListSupport extends Model
{
    use SoftDeletes;

    const STATUS_BUILDING = 1;

    const STATUS_READY    = 2;

    /** @var array */
    protected $guarded = [
        'id',
    ];

    /** @var SuppressionListSupportContent */
    private $content;

    /** @var array */
    private $queue = [];

    private $queueCount = 0;

    /**
     * @param $content
     * @param  int  $id
     *
     * @return $this
     */
    public function addContentToQueue($content, $id = 0)
    {
        $this->getContent()->addContentToQueue($content, $id);

        return $this;
    }

    /**
     * @return SuppressionListSupportContent
     */
    private function getContent()
    {
        if (!$this->content) {
            $this->content = new SuppressionListSupportContent([], $this);
            $this->content->createTableIfNotExists();
        }

        return $this->content;
    }

    /**
     * @return $this
     */
    public function persistQueue()
    {
        if ($this->content) {
            $this->content->persistQueue();
        }

        return $this;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function lists()
    {
        return $this->belongsToMany(SuppressionList::class);
    }
}
