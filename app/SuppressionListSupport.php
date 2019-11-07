<?php

namespace App;

use App\Helpers\HashHelper;
use App\Jobs\SuppressionListSupportContentBuild;
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

    /** @var SuppressionListContent */
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
     * @return SuppressionListContent
     * @throws \Exception
     */
    private function getContent()
    {
        if (!$this->content) {
            $this->content = new SuppressionListContent([], $this);
            $this->content->createTableIfNotExists();
        }

        return $this->content;
    }

    /**
     * @return $this
     */
    public function finish()
    {
        $this->persistQueue();
        if ($this->content) {
            $this->content->finish();

            $this->status = self::STATUS_READY;
            $this->save();

            // Build support for additional hash types.
            if (null === $this->hash_type) {
                $list = $this->list()->getRelated()->withoutTrashed()->first();
                if (!$list) {
                    throw new Exception(__('Suppression list no longer exists.'));
                }
                foreach ((new HashHelper())->listChoices() as $algo => $name) {
                    // Create new support if it doesn't already exist.
                    $newSupport = self::withoutTrashed()
                        ->where('suppression_list_id', $list->id)
                        ->where('column_type', $this->column_type)
                        ->where('hash_type', $algo)
                        ->first();
                    if (!$newSupport) {
                        $newSupport = new self([
                            'suppression_list_id' => $list->id,
                            'status'              => self::STATUS_BUILDING,
                            'column_type'         => $this->column_type,
                            'hash_type'           => $algo,
                        ]);
                    } else {
                        $newSupport->status = self::STATUS_BUILDING;
                    }
                    $newSupport->save();
                    SuppressionListSupportContentBuild::dispatch($newSupport->id)->onQueue('build');
                }
            }
        }

        return $this;
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
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function list()
    {
        return $this->belongsTo(SuppressionList::class);
    }
}
