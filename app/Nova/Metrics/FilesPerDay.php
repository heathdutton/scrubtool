<?php

namespace App\Nova\Metrics;

use App\File;
use DateInterval;
use DateTimeInterface;
use Illuminate\Http\Request;
use Laravel\Nova\Metrics\Trend;

class FilesPerDay extends Trend
{
    /**
     * Calculate the value of the metric.
     *
     * @param  Request  $request
     *
     * @return mixed
     */
    public function calculate(Request $request)
    {
        return $this->countByDays($request, File::class);
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges()
    {
        return [
            30 => '30 Days',
            60 => '60 Days',
            90 => '90 Days',
        ];
    }

    /**
     * Determine for how many minutes the metric should be cached.
     *
     * @return  DateTimeInterface|DateInterval|float|int
     */
    public function cacheFor()
    {
        return now()->addMinutes(5);
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'files-per-day';
    }
}
