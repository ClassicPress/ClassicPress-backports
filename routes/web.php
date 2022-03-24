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

// Index
Route::get('/', 'UpstreamCommitsList@index')->name('home');

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

// Log out
Route::get(
    'logout',
    'Auth\LoginController@logout'
)->name('logout');

// Branches
Route::get('branches/wp-4.9', 'UpstreamCommitsList@showBranch49');
Route::get('branches/wp-5.0', 'UpstreamCommitsList@showBranch50');
Route::get('branches/wp-5.1', 'UpstreamCommitsList@showBranch51');
Route::get('branches/wp-5.2', 'UpstreamCommitsList@showBranch52');
Route::get('branches/wp-5.3', 'UpstreamCommitsList@showBranch53');
Route::get('branches/wp-5.4', 'UpstreamCommitsList@showBranch54');
Route::get('branches/wp-5.5', 'UpstreamCommitsList@showBranch55');
Route::get('branches/wp-5.6', 'UpstreamCommitsList@showBranch56');
Route::get('branches/wp-5.7', 'UpstreamCommitsList@showBranch57');
Route::get('branches/wp-5.8', 'UpstreamCommitsList@showBranch58');
Route::get('branches/wp-5.9', 'UpstreamCommitsList@showBranch59');
Route::get('branches/wp-trunk', 'UpstreamCommitsList@showBranchTrunk');
