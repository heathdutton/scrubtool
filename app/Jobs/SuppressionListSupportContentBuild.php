<?php

namespace App\Jobs;

use App\Helpers\HashHelper;
use App\SuppressionListContent;
use App\SuppressionListSupport;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class SuppressionListSupportContentBuild implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $deleteWhenMissingModels = true;

    /** @var int */
    private $supportId;

    /** @var int */
    private $startId;

    /** @var int */
    private $endId;

    /**
     * SuppressionListSupportContentBuild constructor.
     *
     * @param $supportId
     * @param  null  $startId
     * @param  null  $endId
     */
    public function __construct($supportId, $startId = null, $endId = null)
    {
        $this->supportId = $supportId;
        $this->startId   = $startId;
        $this->endId     = $endId;
        $this->queue     = 'build';
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
        /** @var SuppressionListSupport $support */
        $support = SuppressionListSupport::withoutTrashed()
            ->find($this->supportId);

        if (!$support) {
            return;
        }

        $list = $support->list()->getRelated()->withoutTrashed()->first();
        if (!$list) {
            $support->delete();

            return;
        }

        $hashHelper = new HashHelper();

        if (!isset($hashHelper->listChoices()[$support->hash_type])) {
            // The algo required is no longer enabled.
            $support->delete();

            return;
        }

        // Get parent support content to fill from.
        $parentSupport = SuppressionListSupport::withoutTrashed()
            ->where('suppression_list_id', $list->id)
            ->where('column_type', $support->column_type)
            ->whereNull('hash_type')
            ->first();

        if (!$parentSupport) {
            return;
        }

        // @todo - Fill the content of the current support with hashed versions of the parent.
        $parentContent = new SuppressionListContent([], $parentSupport);
        $parentContent->createTableIfNotExists();
        if ($this->startId && $this->endId) {
            $parentContent->whereBetween('id', [$this->startId, $this->endId]);
        }
        $content = new SuppressionListContent([], $support);
        $content->createTableIfNotExists();

        $algo = $support->hash_type;
        $parentContent->chunkById(SuppressionListContent::BATCH_SIZE,
            function ($parentContents) use ($content, $hashHelper, $algo) {
                foreach ($parentContents as $parentContent) {
                    $value = $parentContent->content;
                    $hashHelper->hash($value, $algo, true);
                    $content->addContentToQueue($value, $parentContent->id);
                }
            }
        );
        $content->finish();

        $support->status = SuppressionListSupport::STATUS_READY;
        $support->save();
    }
}
