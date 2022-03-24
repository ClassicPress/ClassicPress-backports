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
            'branches' => ['4.9', '5.0', '5.1', '5.2', '5.3', '5.4', '5.5', '5.6', '5.7', '5.8', '5.9', 'trunk'],
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
     * Display commits for the `wp-5.3` branch.
     */
    public function showBranch53() {
        // c67b47c66 is `git merge-base wp-5.3 wp-trunk` (the last commit that WP
        // 5.3 and trunk have in common)
        return $this->showBranch('wp-5.3', 'c67b47c66');
    }

    /**
     * Display commits for the `wp-5.4` branch.
     */
    public function showBranch54() {
        // 66f510bda is `git merge-base wp-5.4 wp-trunk` (the last commit that WP
        // 5.4 and trunk have in common)
        return $this->showBranch('wp-5.4', '66f510bda');
    }

    /**
     * Display commits for the `wp-5.5` branch.
     */
    public function showBranch55() {
        // ed82e57f8c is `git merge-base wp-5.5 wp-trunk` (the last commit that WP
        // 5.5 and trunk have in common)
        return $this->showBranch('wp-5.5', 'ed82e57f8c');
    }

    /**
     * Display commits for the `wp-5.6` branch.
     */
    public function showBranch56() {
        // af54a08642 is `git merge-base wp-5.6 wp-trunk` (the last commit that WP
        // 5.6 and trunk have in common)
        return $this->showBranch('wp-5.6', 'af54a08642');
    }

    /**
     * Display commits for the `wp-5.7` branch.
     */
    public function showBranch57() {
        // e2e0ff05c2 is `git merge-base wp-5.7 wp-trunk` (the last commit that WP
        // 5.7 and trunk have in common)
        return $this->showBranch('wp-5.7', 'e2e0ff05c2');
    }

    /**
     * Display commits for the `wp-5.8` branch.
     */
    public function showBranch58() {
        // 47b4353470 is `git merge-base wp-5.8 wp-trunk` (the last commit that WP
        // 5.8 and trunk have in common)
        return $this->showBranch('wp-5.8', '47b4353470');
    }

    /**
     * Display commits for the `wp-5.9` branch.
     */
    public function showBranch59() {
        // 96713b9292 is `git merge-base wp-5.9 wp-trunk` (the last commit that WP
        // 5.9 and trunk have in common)
        return $this->showBranch('wp-5.9', '96713b9292');
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
