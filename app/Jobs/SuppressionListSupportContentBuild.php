<?php

namespace App\Jobs;

use App\Helpers\HashHelper;
use App\Models\SuppressionList;
use App\Models\SuppressionListContent;
use App\Models\SuppressionListSupport;
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
    public $timeout = 21600;

    /** @var bool */
    private $replace = false;

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
     * @param  bool  $replace
     * @param  null  $startId
     * @param  null  $endId
     */
    public function __construct($supportId, $replace = false, $startId = null, $endId = null)
    {
        $this->supportId = $supportId;
        $this->replace   = $replace;
        $this->startId   = $startId;
        $this->endId     = $endId;
        $this->queue     = 'build';
    }

    /**
     * @throws Exception
     */
    public function handle()
    {
        /** @var SuppressionListSupport $suppressionListSupport */
        $suppressionListSupport = SuppressionListSupport::query()->findOrFail($this->supportId);

        /** @var SuppressionList $suppressionList */
        $suppressionList = $suppressionListSupport->suppressionList;
        if (!$suppressionList) {
            $suppressionListSupport->delete();

            return;
        }

        $hashHelper = new HashHelper();

        if (!isset($hashHelper->listChoices()[$suppressionListSupport->hash_type])) {
            // The algo required is no longer enabled.
            $suppressionListSupport->delete();

            return;
        }

        // Get parent support content to fill from.
        $parentSupport = $suppressionList->suppressionListSupports
            ->where('column_type', $suppressionListSupport->column_type)
            ->where('hash_type', null)
            ->first();

        if (!$parentSupport) {
            return;
        }

        if ($this->replace) {
            $suppressionListSupport->status = SuppressionListSupport::STATUS_TO_BE_REPLACED;
            $suppressionListSupport->save();
        }

        // Fill the content of the current support with hashed versions of the parent.
        $parentContent = new SuppressionListContent([], $parentSupport);
        $parentContent->createTableIfNotExists();
        if ($this->startId && $this->endId) {
            $parentContent->whereBetween('id', [$this->startId, $this->endId]);
        }
        $content = new SuppressionListContent([], $suppressionListSupport);
        $content->createTableIfNotExists();

        $algo = $suppressionListSupport->hash_type;
        $parentContent->chunkById(SuppressionListContent::BATCH_SIZE,
            function ($parentContents) use ($content, $hashHelper, $algo) {
                foreach ($parentContents as $parentContent) {
                    $value = $parentContent->content;
                    $hashHelper->hash($value, $algo, true);
                    $content->addContentToQueue($value, $parentContent->id);
                }
            }
        );

        $persisted = $content->finish();
        if ($content->isReplacement()) {
            $suppressionListSupport->count = $persisted;
        } else {
            $suppressionListSupport->count += $persisted;
        }
        $suppressionListSupport->status = SuppressionListSupport::STATUS_READY;
        $suppressionListSupport->save();
    }

    /**
     * @param  Exception  $exception
     *
     * @throws Exception
     */
    public function failed(Exception $exception)
    {
        report($exception);
        /** @var SuppressionListSupport $suppressionListSupport */
        $suppressionListSupport = SuppressionListSupport::query()->find($this->supportId);
        if ($suppressionListSupport) {
            $suppressionListSupport->status = SuppressionListSupport::STATUS_ERROR;
            $suppressionListSupport->delete();
        }
    }
}
