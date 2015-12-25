<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', [
	'as' => 'home',
	'uses' => function () {
		return view('home');
	}
]);
Route::post('/expireCOOKIE', [
	'as' => 'expireCOOKIE',
	'uses' => 'bc@clearTOKEN'
]);
Route::post('/fsearch', [
	'as' => 'fsearch',
	'uses' => 'bc@fsearch'
]);
Route::get('/download/{videoID}', [
	'as' => 'download',
	'uses' => 'bc@download'
]);
Route::get('/downloadlist', [
	'as' => 'downloadlist',
	'uses' => 'bc@downloadlist'
]);
Route::get('/getPROGRESS/{filename}', [
	'as' => 'getPROGRESS',
	'uses' => 'bc@getPROGRESS'
]);
