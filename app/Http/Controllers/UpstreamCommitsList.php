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
     * Display commits for the `wp-5.1` branch.
     */
    public function showBranch51() {
        // 3ec31001 is `git merge-base wp-5.1 wp-trunk` (the last commit that WP
        // 5.1 and trunk have in common)
        return $this->showBranch('wp-5.1', '3ec31001');
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
     * Determine which ClassicPress commits are backports of WP commits.
     */
    public function getBackportedCommits() {
        $git = new GitRepository();

        $commits = $git->log("LAST_WP_COMMIT..origin/develop");
        // HACK: Array to object
        $commits = json_decode(json_encode($commits), false);
        $backports = [];

        foreach ($commits as $commit) {
            $n = preg_match_all(
                '#Merges .* WordPress/wordpress-develop@([a-f-0-9]+) to ClassicPress#mi',
                $commit->body,
                $matches
            );
            if ($n > 0) {
                for ($i = 0; $i < $n; $i++) {
                    $hash = $matches[1][$i];
                    if (strlen($hash) < 10) {
                        $result = $git->run('rev-parse', $hash);
                        $result->assertSuccess("git rev-parse $hash failed");
                        $hash = trim($result->getStdOut());
                    }
                    $backports[substr($hash, 0, 10)] = $commit;
                }
            }
        }

        return $backports;
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
        $backports = $this->getBackportedCommits();

        // HACK: Array to object
        $commits = json_decode(json_encode($commits), false);

        foreach ($commits as $commit) {
            $commit->github_link = sprintf(
                'https://github.com/WordPress/wordpress-develop/commit/%s',
                $commit->commitHash
            );

            $ok = preg_match(
                '#^git-svn-id: https://develop.svn.wordpress.org/[^@\s]*@(\d+) #m',
                $commit->body,
                $matches
            );
            if ($ok) {
                $commit->svn_id = (int) $matches[1];
                $commit->trac_link = sprintf(
                    'https://core.trac.wordpress.org/changeset/%d',
                    $commit->svn_id
                );
            }

            $commit->github_link = sprintf(
                'https://github.com/WordPress/wordpress-develop/commit/%s',
                $commit->commitHash
            );

            $backport = $backports[substr($commit->commitHash, 0, 10)] ?? null;
            if ($backport) {
                $backport->github_link = sprintf(
                    'https://github.com/ClassicPress/ClassicPress/commit/%s',
                    $backport->commitHash
                );
                $commit->backport = $backport;
            }
        }

        return view('branch', compact('branch', 'user', 'commits'));
    }
}
