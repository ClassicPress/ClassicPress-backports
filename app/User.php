<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

use GuzzleHttp\Client;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
        'username', 'avatar', 'github_token', 'github_permissions',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'github_permissions' => 'array',
    ];

    /**
     * Refresh the user's permissions from GitHub (if needed).
     */
    public function refreshPermissions() {
        if (
            !empty($this->github_permissions) &&
            time() - $this->github_permissions['_updated'] < 30*60
        ) {
            return $this;
        }

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
                $this->username
            ), [
                'headers' => ['Authorization' => 'token ' . $this->github_token],
                'http_errors' => false,
            ]
        );
        if ($response->getStatusCode() === 200) {
            $permission = json_decode($response->getBody(), true)['permission'];
            if ($permission !== 'none') {
                $github_permissions["$repo_owner/$repo_name"] = $permission;
            }
        } // Otherwise: no permissions

        $github_permissions['_updated'] = time();
        $this->github_permissions = $github_permissions;

        $this->save();
        return $this;
    }

    /**
     * Returns whether the user has write access to the application's GitHub
     * repository.
     *
     * @return bool
     */
    public function hasWriteAccess() {
        $repo_owner = config('app.repo.owner');
        $repo_name = config('app.repo.name');

        $this->refreshPermissions();
        $permissions = $this->github_permissions["$repo_owner/$repo_name"] ?? null;
        return ($permissions === 'write' || $permissions === 'admin');
    }
}
