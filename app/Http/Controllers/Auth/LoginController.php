<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            )])
            ->scopes(['repo'])
            ->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback(Request $request, $slug)
    {

      if ($slug == 'network') {
        $code = $request->input('code');
        $state = $request->input('state');
        return redirect('https://classicpress.net/?code='.$code.'&state='.$state);
      }elseif ($slug == 'bots') {
        try {
            $gh_user = Socialite::driver('github')->user();

            // Tell Laravel that we are logged in
            $user = User::whereEmail($gh_user->getEmail())->first();
            $attributes = [
                'name'         => $gh_user->getName(),
                'avatar'       => $gh_user->getAvatar(),
                'github_token' => $gh_user->token,
            ];
            if ($user) {
                $user->fill($attributes);
                $user->save();
            } else {
                $attributes += [
                    'email'    => $gh_user->getEmail(),
                    'username' => $gh_user->getNickname(),
                    'password' => Hash::make(str_random(64)),
                ];
                $user = User::create($attributes);
            }
            Auth::login($user, true);

            $user->refreshPermissions();

            return redirect('/')
                ->with('status', 'Welcome, ' . $user->username);

        } catch (\Laravel\Socialite\Two\InvalidStateException $ex) {
            return redirect('login/github');
        }
      }
    }

    public function handleFiderOAuth(Request $request)
    {
      return redirect('https://login.fider.io/oauth/_geqncg2rpa/callback');
    }

    /**
     * Log the current user out of the system.
     *
     * @return \Illuminate\Http\Response
     */
    public function logout() {
        Auth::logout();
        return redirect('/')->with('status', 'Logged out');
    }
}
