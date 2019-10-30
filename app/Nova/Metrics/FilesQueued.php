<?php

namespace App\Nova\Metrics;

use App\File;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Value;

class FilesQueued extends Value
{
    /**
     * Calculate the value of the metric.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function calculate(Request $request)
    {
        // return 0; // File::whereNotIn('status', [File::STATUS_STOPPED, File::STATUS_WHOLE])->count();
        return $this->count($request, File::class);
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        return [
            // 30 => '30 Days',
            // 60 => '60 Days',
            // 365 => '365 Days',
            // 'TODAY' => 'Today',
            // 'MTD' => 'Month To Date',
            // 'QTD' => 'Quarter To Date',
            // 'YTD' => 'Year To Date',
        ];
    }

    /**
     * Determine for how many minutes the metric should be cached.
     *
     * @return  \DateTimeInterface|\DateInterval|float|int
     */
    public function cacheFor()
    {
        // return now()->addMinutes(5);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'files-queued';
    }
}
