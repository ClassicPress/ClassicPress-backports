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

Route::get(
    '/',
    'HomeController@index'
)->name('home');

// GitHub login
Route::get(
    'login/github',
    'Auth\LoginController@redirectToProvider'
)->name('login');

// GitHub login: OAuth callback for Fider
Route::get(
    'login/github/callback/',
    'Auth\LoginController@handleFiderOAuth'
);

// GitHub login: OAuth callback catch all
Route::get(
    'login/github/callback/{slug}',
    'Auth\LoginController@handleProviderCallback'
);

// get commits
Route::get(
    'commits',
    'WPCommitsController@index'
);

// Log out
Route::get(
    'logout',
    'Auth\LoginController@logout'
)->name('logout');

// Branches
Route::get('branches/wp-4.9', 'UpstreamCommitsList@showBranch49');
Route::get('branches/wp-trunk', 'UpstreamCommitsList@showBranchTrunk');
