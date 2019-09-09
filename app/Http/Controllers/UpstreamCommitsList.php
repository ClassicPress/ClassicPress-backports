<?php

namespace App\Http\Controllers;

use App\GitRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class UpstreamCommitsList extends Controller
{

    /**
     * Display a list of available branches.
     */
    public function index() {
        return view('index', [
            'user' => Auth::user(),
            'branches' => ['4.9', '5.0', '5.1', '5.2', 'trunk'],
        ]);
    }

    /**
     * Display commits for the `wp-4.9` branch.
     */
    public function showBranch49() {
        // d7b6719f is the `LAST_WP_COMMIT` tag (the last commit that
        // ClassicPress shares in common with WP)
        return $this->showBranch('wp-4.9', 'd7b6719f');
    }

    /**
     * Display commits for the `wp-5.0` branch.
     */
    public function showBranch50() {
        // 5d477aa7 is WP 4.9.8 (the parent commit of the WP 5.0 branch)
        return $this->showBranch('wp-5.0', '5d477aa7');
    }

    /**
     * Display commits for the `wp-5.1` branch.
     */
    public function showBranch51() {
        // 3ec31001 is `git merge-base wp-5.1 wp-trunk` (the last commit that WP
        // 5.1 and trunk have in common)
        return $this->showBranch('wp-5.1', '3ec31001');
    }

    /**
     * Display commits for the `wp-5.2` branch.
     */
    public function showBranch52() {
        // dc512708 is `git merge-base wp-5.2 wp-trunk` (the last commit that WP
        // 5.2 and trunk have in common)
        return $this->showBranch('wp-5.2', 'dc512708');
    }

    /**
     * Display commits for the `wp-trunk` branch.
     */
    public function showBranchTrunk() {
        // ff6114f8 is `git merge-base wp-4.9 wp-trunk` (the last commit that
        // WP 4.9 and trunk have in common)
        return $this->showBranch('wp-trunk', 'ff6114f8');
    }

    /**
     * Display commits for a branch.
     *
     * @param  string $branch
     * @param  string $show_commits_after
     *
     * @return \Illuminate\Http\Response
     */
    public function showBranch(string $branch, string $show_commits_after)
    {
        $git = new GitRepository();

        $user = Auth::user();
        $can_write = $user && $user->hasWriteAccess();

        if ($can_write) {
            $git->lock();
            $git->fetchIfStale();
            $git->run('checkout', 'origin/develop', '-B', 'develop');
            $git->updateLinkedChangesetsIfStale();
            $git->updateBackportedCommitsIfStale();
            $git->unlock();
        }

        $commits = $git->log("$show_commits_after..$branch");
        $commits = $git->parseCommitsAfterLog($commits,true);

        return view('branch', compact('branch', 'user', 'commits'));
    }
}
