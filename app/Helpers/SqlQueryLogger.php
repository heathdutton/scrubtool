<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SqlQueryLogger
{
    /**
     * @var string
     */
    private $logMethod;

    /**
     * SqlQueryLogger constructor.
     * @param string|null $logMethod
     */
    public function __construct($logMethod = null)
    {
        $this->logMethod = $logMethod ?? 'info';
    }

    /**
     * Bind logging to DB::listen event
     * @return mixed
     */
    public function bindQueryLogger()
    {
        //If enabled, all SQL queries will be streamed to the INFO logs.
        if ($this->checkConfig()) {
            return DB::listen(function ($query) {
                $this->sendToLog(
                    $this->getRawSql($query)
                );
            });
        }
    }

    /**
     * @param $query
     * @return string
     */
    private function getRawSql($query)
    {
        // Simulate ORM's treatment of integers/strings.
        $bindings = [];
        foreach ($query->bindings as $value) {
            if (is_int($value)) {
                $bindings[] = $value;
            } else {
                $bindings[] = "'".$value."'";
            }
        }

        return vsprintf(str_replace('?', '%s', $query->sql), $bindings);
    }

    /**
     * @param $string
     * @return mixed
     */
    private function sendToLog($string)
    {
        return Log::{$this->logMethod}($string);
    }

    /**
     * @return bool
     */
    private function checkConfig()
    {
        return (bool) config('app.log_sql');
    }
}
