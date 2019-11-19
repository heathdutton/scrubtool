<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('app.debug')) {
            DB::listen(function ($query) {
                // Simulate ORM's treatment of integers/strings.
                $bindings = [];
                foreach ($query->bindings as $value) {
                    if (is_int($value)) {
                        $bindings[] = $value;
                    } else {
                        $bindings[] = "'".$value."'";
                    }
                }
                $rawSql = vsprintf(str_replace('?', '%s', $query->sql), $bindings);
                Log::debug('SQL: '.$rawSql);
            });
        }
    }
}
