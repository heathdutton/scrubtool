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
     *
     * @param  string|null  $logMethod
     */
    public function __construct($logMethod = null)
    {
        $this->logMethod = $logMethod ?? 'info';
    }

    /**
     * Bind logging to DB::listen event
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

        return null;
    }

    /**
     * @return bool
     */
    private function checkConfig()
    {
        return (bool) config('app.log_sql');
    }

    /**
     * @param $string
     *
     * @return mixed
     */
    private function sendToLog($string)
    {
        return Log::{$this->logMethod}($string);
    }

    /**
     * @param $query
     *
     * @return string
     */
    private function getRawSql($query)
    {
        // Simulate ORM's treatment of integers/strings.
        $sqlString = $query->sql;
        $bindings  = $query->bindings;

        while ($bindings && $pos = strpos($sqlString, '?')) {
            $value = array_shift($bindings);
            if (!is_int($value)) {
                // Not perfect, but fairly good binary detection.
                if (
                    !mb_detect_encoding($value)
                    || preg_match('~[^\x20-\x7E\t\r\n\a].*~', $value) > 0
                ) {
                    $value = 'BINARY';
                }
                $value = "'".$value."'";
            }
            $sqlString = substr_replace($sqlString, $value, $pos, 1);
        }

        return $sqlString;
    }
}
