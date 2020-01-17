<?php

namespace App\Console\Commands;

use App\Models\FileDownload;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ActionSummarize extends Command
{
    protected $signature = 'action:summarize';

    protected $description = 'Summarizes action data by hour and day.';

    protected $classes = [FileDownload::class];

    protected $timeLimit = '+59 minutes';

    /**
     * The total time limit will be roughly distributed over available classes and operations.
     *
     * @throws \Exception
     */
    public function handle()
    {
        $start      = new Carbon();
        $tsStop     = new Carbon($this->timeLimit);
        $types      = ['hourly', 'daily'];
        $seconds    = floor($tsStop->diffInSeconds(new Carbon(), true) / (count($this->classes) * count($types)));
        $classTypes = [];
        foreach ($this->classes as $class) {
            $classTypes[$class] = $types;
        }

        // Generate daily summaries.
        while ($classTypes && new Carbon() < $tsStop) {
            foreach ($classTypes as $class => $types) {
                foreach ($types as $t => $type) {
                    $model = new $class;
                    /** @var FileDownload $model */
                    $persisted = $model->{$type}()
                        ->createTableIfNotExists()
                        ->generate(min((clone $start)->addSeconds($seconds), $tsStop));
                    // Stop attempting to generate if no data persisted. We'll assume the generation is complete.
                    if (!$persisted) {
                        unset($classTypes[$class][$t]);
                        if (!$classTypes[$class]) {
                            unset($classTypes[$class]);
                        }
                    }
                }
            }
        }
    }
}
