<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\WPCommits;

class HomeController extends Controller
{
    public function index(Request $request)
    {
      $user = $request->user();

      $commits = WPCommits::orderBy('id', 'desc')->get();
      return view('home', compact('user', 'commits'));


    }
}
