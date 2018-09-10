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

Route::get('/', function () {
    return view('welcome');
});

// GitHub login
Route::get(
    'login/github',
    'Auth\LoginController@redirectToProvider'
)->name('login');

// GitHub login: OAuth callback
Route::get(
    'login/github/callback',
    'Auth\LoginController@handleProviderCallback'
);
