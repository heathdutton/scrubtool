<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/v1/scrub/{idToken}/{record}', 'Api\V1\ScrubController@single')
    ->where('idToken', '.+z.+')->name('api.suppressionList.scrub');
