<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Session;
use Socialite;

use GuzzleHttp\Client;

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
            ->scopes(['repo'])
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

            Session::put('user', $user);

            // Query the GitHub API for the current user's permissions
            $repo_owner = config('app.repo.owner');
            $repo_name = config('app.repo.name');
            $github_permissions = ["$repo_owner/$repo_name" => null];
            $client = new Client([
                'base_uri' => 'https://api.github.com/',
                'timeout'  => 2,
            ]);
            $response = $client->request(
                'GET',
                sprintf(
                    '/repos/%s/%s/collaborators/%s/permission',
                    $repo_owner,
                    $repo_name,
                    $user->getNickname()
                ), [
                    'headers' => ['Authorization' => 'token ' . $user->token],
                    'http_errors' => false,
                ]
            );
            if ($response->getStatusCode() === 200) {
                $permission = json_decode($response->getBody(), true)['permission'];
                if ($permission !== 'none') {
                    $github_permissions["$repo_owner/$repo_name"] = $permission;
                }
            } // Otherwise: no permissions
            Session::put('github_permissions', $github_permissions);

            return redirect($request->input('redirect') ?: '/')
                ->with('status', 'Welcome, ' . $user->getNickname());

        } catch (\Laravel\Socialite\Two\InvalidStateException $ex) {
            return redirect('login/github');
        }
    }

    /**
     * Log the current user out of the system.
     *
     * @return \Illuminate\Http\Response
     */
    public function logout() {
        Session::flush();
        return redirect('/')->with('status', 'Logged out');
    }
}
