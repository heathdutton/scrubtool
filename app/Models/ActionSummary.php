<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class ActionSummary
 *
 * General class for summarizing actions by hour or day.
 * Leans on the timezone of the database for daily summary. Recommend UTC.
 *
 * @package App\Models
 */
class ActionSummary extends Model
{
    /** @var int */
    const BATCH_SIZE = 10007;

    /** @var string */
    const COUNT = 'row_count';

    /** @var string */
    const TABLE_SUFFIX = 'sum';

    /** @var string This timestamp column exist in the original table and will be used as the date for correlation/generation. */
    const TS = 'created_at';

    /** @var string */
    const TYPE_DAILY = 'd';

    /** @var string */
    const TYPE_HOURLY = 'h';

    /** @var bool */
    public $timestamps = false;

    /** @var ActionAbstract */
    public $parent;

    /** @var int */
    public $summaryType;

    /** @var bool */
    public $tableExists = false;

    /** @var array Columns to act as unique key/s */
    public $keyColumns = [];

    /** @var array Columns to be summed by the interval */
    public $sumColumns = [];

    /** @var int */
    public $maxDaysBack = 365;

    /** @var array */
    protected $guarded = ['id'];

    /** @var array */
    protected $attributes = [];

    /** @var array */
    private $queue = [];

    /** @var array */
    private $deleteQueue = [];

    /** @var int */
    private $queueCount = 0;

    /** @var int */
    private $persistedCount = 0;

    /**
     * ActionSummary constructor.
     *
     * @param  array  $attributes
     * @param  string  $parent
     * @param  string  $summaryType
     * @param  int  $maxDaysBack
     */
    public function __construct(
        array $attributes = [],
        $parent = null,
        $summaryType = self::TYPE_DAILY,
        $maxDaysBack = 365
    ) {

        if ($parent) {
            $this->attributes  = $attributes;
            $this->parent      = is_string($parent) ? (new $parent) : $parent;
            $this->summaryType = $summaryType;
            $this->maxDaysBack = $maxDaysBack;

            $this->setTable($this->tableName());
            foreach ($this->parent->getCasts() as $column => $cast) {
                if ('int' == $cast && 'id' !== $column && '_id' !== substr($column, -3)) {
                    $this->sumColumns[] = $column;
                }
            }
        }

        parent::__construct($attributes);

        return $this;
    }

    /**
     * @return string
     */
    private function tableName()
    {
        $pieces = [
            $this->parent->getTable(),
            self::TABLE_SUFFIX,
            $this->summaryType,
        ];

        return implode('_', $pieces);
    }

    /**
     * Generate the table if it doesn't exist to fill the data based on the Suppression List Support type.
     *
     * @return $this
     */
    public function createTableIfNotExists()
    {
        if (
            !$this->tableExists
            && !Schema::hasTable($this->getTable())
        ) {
            Schema::create($this->getTable(),
                function (Blueprint $table) {
                    // $table->bigIncrements('id');
                    $table->timestamp(self::TS, 0);
                    foreach ($this->keyColumns as $column) {
                        $table->bigInteger($column, false, true);
                        $table->index([self::TS, $column]);
                    }
                    foreach ($this->sumColumns as $column) {
                        $table->bigInteger($column, false, true)->default(0);
                    }
                    $table->bigInteger(self::COUNT, false, true)->default(0);
                });

            $this->tableExists = true;
        }

        return $this;
    }

    /**
     * @param  Carbon|null  $tsStop  Optional time to terminate generation of data.
     * @param  bool  $rebuild  Indicates we will be regenerating data, deleting where it previously exists.
     *
     * @return int
     * @throws \Exception
     */
    public function generate(
        Carbon $tsStop = null,
        $rebuild = false
    ) {
        $ts    = null; // Time slot we are always trying to generate.
        $tsEnd = null; // The last time slot we will attempt to generate.
        while (
            (!$tsStop || new Carbon() < $tsStop)
            && $this->nextTimeSlot($rebuild, $ts, $tsEnd)
        ) {
            if ($rebuild & $this->keyColumns) {
                $this->deleteQueue[] = $ts;
            }
            $selects = ['COUNT(*) as '.self::COUNT];
            foreach ($this->sumColumns as $column) {
                $selects[] = "SUM('{$column}') as '{$column}'";
            }
            $data = DB::table($this->source()->getTable())
                ->select(DB::raw(implode(', ', $selects)))
                ->where('created_at', '>=', $ts)
                ->where('created_at', '<', $this->iterateTime(clone $ts))
                ->groupBy([DB::raw("DATE('".self::TS."')")] + $this->keyColumns)
                ->get()
                ->toArray();
            if ($data) {
                $this->addDataToQueue($data);
            }
        }
        $this->persistQueue();

        return $this->persistedCount;
    }

    /**
     * Traverse backward to find first non-existent time slot, with a starting timestamp.
     *
     * @param  bool  $rebuild
     * @param  Carbon|null  $ts
     * @param  Carbon|null  $tsEnd
     *
     * @return bool
     * @throws \Exception
     */
    public function nextTimeSlot($rebuild = false, Carbon &$ts = null, Carbon &$tsEnd = null)
    {
        if ($ts === null) {
            $ts = $this->startTimestamp();
            // On the first run, jump back to the first filled source data.
            if ($newestSource = DB::table($this->source()->getTable())
                ->where(self::TS, '<', $ts)
                ->orderByDesc(self::TS)
                ->first()
            ) {
                $ts = new Carbon($newestSource->{self::TS});
                $this->normalizeTime($ts);

                $firstRun = true;
                if ($tsEnd === null) {
                    $tsEnd = $this->endTimestamp($ts);
                }
            } else {
                // No more data to generate.
                return false;
            }
        } else {
            $this->iterateTime($ts);
            $firstRun = false;
        }
        if (!$rebuild) {
            if ($firstRun) {
                // Find the first empty slot to know where to begin.
                if ($oldestDestination = DB::table($this->destination()->getTable())
                    ->where(self::TS, '<', $ts)
                    ->orderby(self::TS)
                    ->first()
                ) {
                    // A record was found, jump back one iteration.
                    $ts = (new Carbon($oldestDestination->{self::TS}, config('app.timezone')));
                    $this->iterateTime($ts);
                }
            } else {
                // On subsequent runs, if the current exists, jump back till empty slot is found.
                while (
                    $ts >= $tsEnd
                    && DB::table($this->getTable())
                        ->where(self::TS, $ts)
                        ->limit(1)
                        ->exists()
                ) {
                    $this->iterateTime($ts);
                }
            }
        }

        return $ts >= $tsEnd;
    }

    /**
     * @return Carbon
     * @throws \Exception
     */
    private function startTimestamp()
    {
        if ($this->summaryType == self::TYPE_HOURLY) {
            return (new Carbon('last hour', config('app.timezone')))
                ->minute(0)->second(0)->milli(0);
        } elseif ($this->summaryType == self::TYPE_DAILY) {
            return (new Carbon('yesterday midnight', config('app.timezone')));
        }
    }

    private function source()
    {
        if ($this->summaryType == self::TYPE_HOURLY) {
            return $this->parent;
        } else {
            return $this->hourly();
        }
    }

    private function hourly()
    {
        return $this->getSummaryByType(self::TYPE_HOURLY);
    }

    protected function getSummaryByType($type)
    {
        if ($this->summaryType == $type) {
            return $this;
        } else {
            return new self($this->attributes, $this->parent, $type, $this->maxDaysBack);
        }
    }

    /**
     * @param  Carbon  $ts
     *
     * @return Carbon
     */
    private function normalizeTime(Carbon $ts)
    {
        if ($this->summaryType == self::TYPE_HOURLY) {
            $ts->minute(0)->second(0)->micro(0);
        } elseif ($this->summaryType == self::TYPE_DAILY) {
            $ts->setTime(0, 0, 0, 0);
        }

        return $ts;
    }

    /**
     * @param  Carbon  $ts
     *
     * @return Carbon
     * @throws \Exception
     */
    private function endTimestamp(Carbon $ts)
    {
        $tsEnd = (clone $ts)->subDays($this->maxDaysBack);
        // If the parent data doesn't go back that far update tsEnd.
        $parentRecord = DB::table($this->source()->getTable())
            ->where(self::TS, '>', $tsEnd)
            ->orderBy(self::TS)
            ->first();
        if ($parentRecord) {
            $tsEnd = (new Carbon($parentRecord->{self::TS}, config('app.timezone')));
            if ($this->summaryType == self::TYPE_DAILY) {
                $tsEnd->hour(0);
            }
            $tsEnd->minute(0)->second(0)->milli(0);
        }

        return $tsEnd;
    }

    /**
     * @param  Carbon  $ts
     *
     * @return Carbon
     */
    private function iterateTime(Carbon $ts)
    {
        if ($this->summaryType == self::TYPE_HOURLY) {
            $ts->subHour();
        } elseif ($this->summaryType == self::TYPE_DAILY) {
            $ts->subDay();
        }
        $this->normalizeTime($ts);

        return $ts;
    }

    private function destination()
    {
        if ($this->summaryType == self::TYPE_HOURLY) {
            return $this->hourly();
        } else {
            return $this->daily();
        }
    }

    private function daily()
    {
        return $this->getSummaryByType(self::TYPE_DAILY);
    }

    /**
     * @param $data
     *
     * @return $this
     */
    public function addDataToQueue($data)
    {
        $this->queue      += $data;
        $this->queueCount += count($data);
        if (0 == $this->queueCount % self::BATCH_SIZE) {
            $this->persistQueue();
        }

        return $this;
    }

    /**
     * @return int
     */
    public function persistQueue()
    {
        if ($this->deleteQueue) {
            DB::table($this->destination()->getTable())
                ->whereIn('created_at', $this->deleteQueue)
                ->delete();
            $this->deleteQueue = [];
        }

        $this->persistedCount += DB::table($this->destination()->getTable())->insertOrIgnore($this->queue);

        $this->queueCount = 0;
        $this->queue      = [];

        return $this->persistedCount;
    }

}
