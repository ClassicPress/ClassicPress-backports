<?php

namespace App\Http\Controllers;

use App\GitRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class UpstreamCommitsList extends Controller
{
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
            $git->unlock();
        }

        $commits = $git->log("$show_commits_after..$branch");

        // HACK: Array to object
        $commits = json_decode(json_encode($commits), false);

        foreach ($commits as $commit) {
            // HACK: Match existing view logic
            $commit->status = 0;
            $commit->sha = $commit->commitHash;
            $commit->html_link = sprintf(
                'https://github.com/WordPress/wordpress-develop/commit/%s',
                $commit->commitHash
            );
        }

        return view('branch', compact('branch', 'user', 'commits'));
    }
}
