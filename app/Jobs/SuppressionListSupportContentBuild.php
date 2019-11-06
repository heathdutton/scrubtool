<?php

namespace App\Jobs;

use App\Helpers\HashHelper;
use App\SuppressionListSupport;
use App\SuppressionListSupportContent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class SuppressionListSupportContentBuild implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public $deleteWhenMissingModels = true;

    /** @var int */
    protected $supportId;

    /** @var int */
    protected $startId;

    /** @var int */
    protected $endId;

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
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        /** @var SuppressionListSupport $support */
        $support = SuppressionListSupport::find($this->supportId);
        if ($support) {
            $hashHelper = new HashHelper();

            if (!isset($hashHelper->listChoices()[$support->hash_type])) {
                // The algo required is no longer enabled.
                $support->delete();

                return;
            }

            // Get parent support content to fill from.
            $parentSupport = SuppressionListSupport::where('suppression_list_id', $support->list()->id)
                ->where('column_type', $support->column_type)
                ->whereNull('hash_type')
                ->first()
                ->get();

            if ($parentSupport) {
                // @todo - Fill the content of the current support with hashed versions of the parent.
                $parentContent = new SuppressionListSupportContent([], $parentSupport);
                if ($this->startId && $this->endId) {
                    $parentContent->whereBetween('id', [$this->startId, $this->endId]);
                }
                $content = new SuppressionListSupportContent([], $support);

                $algo = $support->hash_type;
                $parentContent->chunkById(SuppressionListSupportContent::BATCH_SIZE,
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
    }
}
