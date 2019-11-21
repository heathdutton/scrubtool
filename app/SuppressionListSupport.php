<?php

namespace App;

use App\Helpers\HashHelper;
use App\Jobs\SuppressionListSupportContentBuild;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuppressionListSupport extends Model
{
    use SoftDeletes;

    const STATUS_BUILDING = 1;

    const STATUS_READY    = 2;

    /*
     * Column Type Values
     */
    const TYPE_AGE        = 1;

    const TYPE_DATETIME   = 2;

    const TYPE_DOB        = 4;

    const TYPE_EMAIL      = 8;

    const TYPE_FLOAT      = 16;

    const TYPE_HASH       = 32;

    const TYPE_INTEGER    = 64;

    const TYPE_L_ADDRESS1 = 128;

    const TYPE_L_ADDRESS2 = 256;

    const TYPE_L_CITY     = 512;

    const TYPE_L_COUNTRY  = 1024;

    const TYPE_L_ZIP      = 2048;

    const TYPE_NAME_FIRST = 4096;

    const TYPE_NAME_LAST  = 8192;

    const TYPE_PHONE      = 16384;

    const TYPE_STRING     = 32768;

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
     * @throws Exception
     */
    public function addContentToQueue($content, $id = 0)
    {
        $this->getContent(true)->addContentToQueue($content, $id);

        return $this;
    }

    /**
     * @param  bool  $create
     *
     * @return SuppressionListContent
     * @throws Exception
     */
    public function getContent($create = false)
    {
        if (!$this->content) {
            $this->content = new SuppressionListContent([], $this);
            if ($create) {
                $this->content->createTableIfNotExists();
            }
        }

        return $this->content;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function finish()
    {
        $persisted = 0;
        if ($this->content) {
            $this->content->finish();

            $this->status = self::STATUS_READY;
            $this->save();

            // Build support for additional hash types, and queue the processes to build them out.
            $persisted = $this->content->getPersistedCount();
            if (null === $this->hash_type && $persisted) {

                if (!$this->suppressionList) {
                    throw new Exception(__('Suppression list parent no longer exists.'));
                }
                foreach ((new HashHelper())->listChoices() as $algo => $name) {
                    // Create new support if it doesn't already exist.
                    $newSupport = self::withoutTrashed()
                        ->where('suppression_list_id', $this->suppressionList->id)
                        ->where('column_type', $this->column_type)
                        ->where('hash_type', $algo)
                        ->first();
                    if (!$newSupport) {
                        $newSupport = new self([
                            'suppression_list_id' => $this->suppressionList->id,
                            'status'              => self::STATUS_BUILDING,
                            'column_type'         => $this->column_type,
                            'hash_type'           => $algo,
                        ]);
                    } else {
                        $newSupport->status = self::STATUS_BUILDING;
                    }
                    $newSupport->save();
                    SuppressionListSupportContentBuild::dispatch($newSupport->id);
                }
            }
        }

        return $persisted;
    }

    /**
     * @return BelongsTo
     */
    public function suppressionList()
    {
        return $this->belongsTo(SuppressionList::class);
    }
}
