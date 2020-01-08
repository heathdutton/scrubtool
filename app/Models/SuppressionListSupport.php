<?php

namespace App\Models;

use App\Helpers\HashHelper;
use App\Jobs\SuppressionListSupportContentBuild;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SuppressionListSupport extends Model
{
    use SoftDeletes;

    /** @var int The first tile the support is being constructed/filled. */
    const STATUS_BUILDING = 1;

    /** @var int Something went wrong with the support creation. */
    const STATUS_ERROR = 2;

    /** @var int The support has been built and is ready for use. */
    const STATUS_READY = 4;

    /** @var int Records are being added to the list, but it can still be used. */
    const STATUS_TO_BE_APPENDED = 4;

    /** @var int A new table of support content is being formed to replace the existing one, however the existing one can still be used in the meantime. */
    const STATUS_TO_BE_REPLACED = 8;

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

    const TYPE_IP         = 65536;

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
     * @param  array  $attributes
     *
     * @return \Illuminate\Support\Collection
     */
    public static function findPreferredSupports(array $attributes = [])
    {
        $suppressionListIds = (array) $attributes['suppression_list_id'];
        $columnType         = $attributes['column_type'] ?? null;
        $hashType           = $attributes['hash_type'] ?? null;
        $status             = $attributes['status'] ?? SuppressionListSupport::STATUS_READY;
        $supportedHashTypes = (new HashHelper())->listChoices();
        $limit              = count($suppressionListIds);
        $q                  = self::query();
        if ($suppressionListIds) {
            $q->whereIn('suppression_list_id', $suppressionListIds);
        }
        if ($columnType) {
            $q->where('column_type', $columnType);
        }
        if (null === $hashType && count($supportedHashTypes)) {
            $limit *= (count($supportedHashTypes) + 1);
            $q->where(function ($q) use ($supportedHashTypes) {
                $q->whereNull('hash_type');
                $q->orWhereIn('hash_type', $supportedHashTypes);
            });
        } elseif ($hashType) {
            $q->where('hash_type', $hashType);
        }
        if ($status) {
            $q->where('status', $status);
        }
        if ($limit > 1) {
            $q->groupBy(['suppression_list_id'])
                ->orderBy('hash_type', 'ASC');
        }

        $q->limit($limit);

        return collect($q->get());
    }

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
            if ($this->content->isReplacement()) {
                $this->count = $persisted;
            } else {
                $this->count += $persisted;
            }
            $this->save();

            // Build support for additional hash types, and queue the processes to build them out.
            if (null === $this->hash_type && $persisted) {

                if (!$this->suppressionList) {
                    throw new Exception(__('Suppression list parent no longer exists.'));
                }
                foreach ((new HashHelper())->listChoices() as $algo => $name) {
                    // Create new support if it doesn't already exist.
                    $newSupport             = self::withTrashed()
                        ->firstOrCreate([
                            'suppression_list_id' => $this->suppressionList->id,
                            'column_type'         => $this->column_type,
                            'hash_type'           => $algo,
                        ], [
                            'status' => self::STATUS_BUILDING,
                        ]);
                    $newSupport->deleted_at = null;
                    $newSupport->status     = self::STATUS_BUILDING;
                    $newSupport->save();
                    SuppressionListSupportContentBuild::dispatch($newSupport->id, $this->content->isReplacement());
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
