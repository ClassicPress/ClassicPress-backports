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
    $user = Session::get('user');
    if ($user) {
        $user = $user->getNickname();
    }
    return '<pre>' . htmlentities(print_r([
        'page' => 'main',
        'user' => $user,
        'github_permissions' => Session::get('github_permissions'),
        'status' => Session::get('status'),
    ], true)) . '</pre>';
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

// Log out
Route::get(
    'logout',
    'Auth\LoginController@logout'
);
