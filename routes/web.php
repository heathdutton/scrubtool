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

Route::get('/dashboard', 'DashboardController@index')->name('home');

Route::get('/files', 'FileController@index')->name('files');
Route::get('/files/new/{mode}', 'FileController@index');
Route::post('/files/new/upload', 'FileController@upload')->name('file.upload');

Route::get('/file', 'FileController@file');

Route::get('/files/{id}', 'FileController@file')->name('file');
Route::post('/files/{id}', 'FileController@store')->name('file.store');
Route::get('/files/{id}/download', 'FileController@download')->name('file.download');
Route::get('/files/{id}/download/{token}', 'FileController@downloadWithToken')->name('file.download.with.token');

Route::get('/lists', 'SuppressionListController@index')->name('suppressionLists');
Route::get('/lists/{id}', 'SuppressionListController@suppressionList')->name('suppressionList');

Route::any('/defaults', 'ActionDefaultsController')->name('defaults');

Route::get('/notification/read/all', 'NotificationController@readAll')->name('notificationReadAll');
