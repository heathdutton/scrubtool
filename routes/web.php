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
Route::post('/files/upload', 'FileController@upload');

Route::get('/file', 'FileController@file');
Route::post('/file', 'FileController@update')->name('file');

Route::get('/lists', 'SuppressionListController@index')->name('lists');
