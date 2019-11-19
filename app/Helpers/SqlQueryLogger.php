<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SqlQueryLogger
{
    public static function bindQueryLogger()
    {
        //If enabled, all SQL queries will be streamed to the INFO logs.
        if (config('app.log_sql')) {
            DB::listen(function ($query) {
                Log::info(
                    json_encode(
                        [
                            'QUERY' => $query->sql,
                            'DATA'  => $query->bindings,
                            'TIME'  => $query->time
                        ]
                    )
                );
            });
        }
    }
}
