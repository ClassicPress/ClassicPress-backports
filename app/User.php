<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

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
        'username', 'avatar',
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
     * Returns whether the user has access to perform write operations.
     *
     * Implemented this way to avoid requesting and storing GitHub API tokens
     * (privileged 'repo' scope required to check membership via their API).
     *
     * @return bool
     */
    public function hasWriteAccess() {
        return in_array($this->username, [
            'frozzare',
            'nylen',
            'scottybo',
            'dsnid3r',
            'Mte90',
            'senlin',
        ]);
    }
}
