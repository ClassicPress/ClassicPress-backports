<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WPCommits extends Model
{
    protected $fillable = [
      'sha',
      'nodeid',
      'message',
      'commit_date',
      'status',
      'decline_response',
      'html_link'
    ];
}
