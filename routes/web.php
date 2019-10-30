<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

Route::get('/', 'WelcomeController@index');

Route::get('/files', 'FilesController@index');
Route::post('/files/update', 'FilesController@update');
Route::post('/files/upload', 'FilesController@upload');
Route::get('/file', 'FilesController@file');

Route::get('/dashboard', 'DashboardController@index')->name('home');
