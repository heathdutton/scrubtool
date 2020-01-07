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
Route::post('/files/{id}/store', 'FileController@store')->name('file.store');
Route::post('/files/{id}/email', 'FileController@email')->name('file.email');
Route::get('/files/{id}/download', 'FileController@download')->name('file.download');
Route::get('/files/{id}/download/{token}', 'FileController@downloadWithToken')->name('file.download.with.token');
Route::get('/files/{id}/{status?}', 'FileController@file');

Route::any('/defaults', 'ActionDefaultsController')->name('defaults');

Route::get('/profile', 'Auth\ProfileController@index')->name('profile');
Route::post('/profile', 'Auth\ProfileController@store')->name('profile.store');

Route::get('/plan', 'Auth\PlanController@index')->name('plan');
Route::post('/plan', 'Auth\PlanController@store')->name('plan.store');

Route::get('/notification/read/all', 'NotificationController@readAll')->name('notificationReadAll');

Route::get('/lists', 'SuppressionListController@index')->name('suppressionLists');
Route::get('/lists/{id}', 'SuppressionListController@suppressionList')->name('suppressionList');
Route::get('/lists/{id}/edit', 'SuppressionListController@edit')->name('suppressionList.edit');
Route::post('/lists/{id}/store', 'SuppressionListController@store')->name('suppressionList.store');
Route::get('/lists/{id}/restore', 'SuppressionListController@restore')->name('suppressionList.restore');
Route::get('/{idToken}', 'SuppressionListShareController@share')->name('suppressionList.share');
