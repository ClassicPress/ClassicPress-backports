<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Session;
use Socialite;

class LoginController extends Controller
{
    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider(Request $request)
    {
        return Socialite::driver('github')
            ->with(['redirect_uri' => (
                config('services.github.redirect')
                . '?redirect=' . $request->input('redirect')
            )])
            ->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback(Request $request)
    {
        try {
            $user = Socialite::driver('github')->user();
            $github_user = Socialite::driver('github')->userFromToken($user->token);

            Session::put('user', $user);
            Session::put('github_user', $github_user);

            return redirect($request->input('redirect') ?: '/');

        } catch (\Laravel\Socialite\Two\InvalidStateException $ex) {
            return redirect('login/github');
        }
    }
}
