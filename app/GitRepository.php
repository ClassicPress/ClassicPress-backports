<?php

namespace App;

use Tivie\Command\Argument;
use Tivie\GitLogParser;
use TQ\Git\Repository\Repository;

class GitRepository {
	private $git = null;
	private $repo_dir = null;
	private $lock = null;
	private $linked_changesets = null;
	private $included_changesets = null;
	private $classicpress_backports = null;

	function __construct() {
		$repo_owner = config('app.repo.owner');
		$repo_name = config('app.repo.name');
		$this->repo_dir = storage_path(sprintf(
			'app/repos/%s/%s',
			$repo_owner,
			$repo_name
		));
		$this->git = Repository::open($this->repo_dir);
		if ($this->git->getRepositoryPath() !== $this->repo_dir) {
			throw new \InvalidArgumentException(
				'The Git repository is not initialized yet'
			);
		}

		$this->linked_changesets = @file_get_contents(
			$this->repo_dir . '/.git/linked-changesets.json'
		);
		if ($this->linked_changesets) {
			$this->linked_changesets = json_decode($this->linked_changesets, true);
		} else {
			// Creating this file for the first time
			$this->lock();
			$this->updateLinkedChangesetsIfStale();
			$this->unlock();
		}

		$this->classicpress_backports = @file_get_contents(
			$this->repo_dir . '/.git/classicpress-backports.json'
		);
		if ($this->classicpress_backports) {
			$this->classicpress_backports = json_decode($this->classicpress_backports, true);
		} else {
			// Creating this file for the first time
			$this->lock();
			$this->updateBackportedCommitsIfStale();
			$this->unlock();
		}

		$this->included_changesets = @file_get_contents(
			$this->repo_dir . '/.git/included-changesets.json'
		);
		if ($this->included_changesets) {
			$this->included_changesets = json_decode($this->included_changesets, true);
		} else {
			// Creating this file for the first time
			$this->lock();
			$this->updateIncludedChangesets();
			$this->unlock();
		}
	}

	public function fetchIfStale($threshold = 10*60) {
		$last_fetch = @filemtime($this->repo_dir . '/.git/FETCH_HEAD');
		if ($last_fetch && time() - $last_fetch <= $threshold) {
			return;
		}

		// git fetch origin
		$this->run('fetch', 'origin')
			->assertSuccess('Failed to fetch remote: origin');
		// git fetch wp (set up 'wp' remote and retry if needed)
		try {
			$this->run('fetch', 'wp')
				->assertSuccess('Failed to fetch remote: wp (try 1)');
		} catch (\Throwable $e) {
			$this->run(
				'remote',
				'add',
				'wp',
				'https://github.com/WordPress/wordpress-develop.git'
			)->assertSuccess('Failed to add remote: wp');
			$this->run('fetch', 'wp')
				->assertSuccess('Failed to fetch remote: wp');
		}

		$this
			->run('checkout', 'origin/develop', '-B', 'develop')
			->assertSuccess('Failed to check out the master branch');
		// https://stackoverflow.com/a/1591255
		$this
			->run('branch', '-f', 'wp-4.9', 'wp/4.9')
			->assertSuccess('Failed to update the wp-4.9 branch');
		$this
			->run('branch', '-f', 'wp-5.0', 'wp/5.0')
			->assertSuccess('Failed to update the wp-5.0 branch');
		$this
			->run('branch', '-f', 'wp-5.1', 'wp/5.1')
			->assertSuccess('Failed to update the wp-5.1 branch');
		$this
			->run('branch', '-f', 'wp-trunk', 'wp/master')
			->assertSuccess('Failed to update the wp-trunk branch');
	}

	/**
	 * (Re)build the map of WP changesets that are linked to each other,
	 * decoded via commit message entries like the following:
	 *
	 * Merges [43785] from the 5.0 branch to trunk.
	 */
	public function updateLinkedChangesetsIfStale($threshold = 10*60) {
		$last_update = @filemtime($this->repo_dir . '/.git/linked-changesets.json');
		if ($last_update && time() - $last_update <= $threshold) {
			// Already up-to-date
			return;
		}

		// Note: not fetching - assuming this is already up-to-date

		$this->linked_changesets = [];

		// This is one day before the date of `git merge-base wp/4.8 wp/master`
		$all_wp_commits = $this->log('--since=2017-05-31', true);
		$all_wp_commits = $this->parseCommitsAfterLog($all_wp_commits);
		$all_wp_commits = array_filter($all_wp_commits, function($c) {
			return !empty($c->svn_id);
		});

		$commits_by_svn_id = [];
		$commits_by_git_hash = [];

		foreach ($all_wp_commits as &$commit) {
			$commits_by_svn_id[$commit->svn_id] = $commit;
			$commits_by_git_hash[$commit->commitHash] = $commit;
		}

		foreach ($all_wp_commits as &$commit) {
			$commit->is_merge_of = [];
			$is_merge_of_svn = [];

			$ok = preg_match(
				'#^(Merge|Backport).*\[\d.*\d\].*\b(into|onto|to)\b#mi',
				$commit->body,
				$matches
			);
			if ($ok) {
				preg_match_all(
					'#\[([\d-,]+)\]#',
					$matches[0],
					$matches2
				);
				foreach ($matches2[1] as $ranges) {
					// See https://github.com/WordPress/wordpress-develop/commit/dc70d3e2
					// and https://github.com/WordPress/wordpress-develop/commit/1f4c84c6
					foreach (explode(',', $ranges) as $range) {
						$limits = explode('-', $range);
						if (count($limits) === 1) {
							$is_merge_of_svn[] = (int)$limits[0];
						} else {
							for ($i = (int)$limits[0]; $i <= (int)$limits[1]; $i++) {
								$is_merge_of_svn[] = $i;
							}
						}
					}
				}
			}

			foreach ($is_merge_of_svn as $m) {
				if (!isset($commits_by_svn_id[$m])) {
					$this->debug(
						"Commit with SVN ID $m not found: "
						. json_encode($commit, JSON_UNESCAPED_SLASHES)
					);
					continue;
				}
				$commit->is_merge_of[] = $commits_by_svn_id[$m];
			}
		}

		foreach ($all_wp_commits as &$commit) {
			// key: commit hash, value: processed or not (starts as false)
			$is_merge_of_recursive = [];
			foreach ($commit->is_merge_of as $m) {
				$is_merge_of_recursive[$m->commitHash] = false;
			}
			$still_processing = true;
			while ($still_processing) {
				$still_processing = false;
				foreach ($is_merge_of_recursive as $hash => $processed) {
					if ($processed) {
						continue;
					}
					$still_processing = true;

					if (!isset($this->linked_changesets[$hash])) {
						$this->linked_changesets[$hash] = [
							'is_merge_of' => [],
							'is_merged_by' => [],
						];
					}
					$this->linked_changesets[$hash]['is_merged_by'][$commit->commitHash] = true;

					if (!isset($this->linked_changesets[$commit->commitHash])) {
						$this->linked_changesets[$commit->commitHash] = [
							'is_merge_of' => [],
							'is_merged_by' => [],
						];
					}
					$this->linked_changesets[$commit->commitHash]['is_merge_of'][$hash] = true;

					$is_merge_of_recursive[$hash] = true;

					// Process merge chains
					if (!isset($commits_by_git_hash[$hash])) {
						$this->debug(
							"Commit with git hash $hash not found"
						);
						continue;
					}

					foreach ($commits_by_git_hash[$hash]->is_merge_of as $m) {
						$this->debug(
							'Merge chain: '
							. json_encode([
								'commit' => $commit,
								'chain' => $m,
							], JSON_UNESCAPED_SLASHES)
						);
						if (!isset($is_merge_of_recursive[$m->commitHash])) {
							$is_merge_of_recursive[$m->commitHash] = false;
						}
					}
				}
			}
		}

		file_put_contents(
			$this->repo_dir . '/.git/linked-changesets.json',
			json_encode(
				$this->linked_changesets,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
			)
		);
	}

	/**
	 * Build the list of WP changesets that are included in ClassicPress via
	 * the WP 4.9 branch prior to the fork.
	 */
	public function updateIncludedChangesets() {
		if (file_exists($this->repo_dir . '/.git/included-changesets.json')) {
			return;
		}

		// ff6114f8 is `git merge-base wp-4.9 wp-trunk` (the last commit that
		// WP 4.9 and trunk have in common)
		$commits = $this->log('ff6114f8..LAST_WP_COMMIT');

		$this->included_changesets = [];
		foreach ($commits as $commit) {
			$this->included_changesets[$commit['commitHash']] = true;
		}

		file_put_contents(
			$this->repo_dir . '/.git/included-changesets.json',
			json_encode(
				$this->included_changesets,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
			)
		);
	}

	/**
	 * Update info about commits already backported to ClassicPress, if needed.
	 */
	public function updateBackportedCommitsIfStale($threshold = 10*60) {
		$last_update = @filemtime($this->repo_dir . '/.git/classicpress-backports.json');
		if ($last_update && time() - $last_update <= $threshold) {
			return;
		}

		// Note: not fetching - assuming this is already up-to-date

		$commits = $this->log("LAST_WP_COMMIT..origin/develop");
		$commits = $this->parseCommitsAfterLog($commits);

		$this->classicpress_backports = [];

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
						$result = $this->run('rev-parse', $hash);
						$result->assertSuccess("git rev-parse $hash failed");
						$hash = trim($result->getStdOut());
					}
					$this->classicpress_backports[substr($hash, 0, 10)] = $commit->commitHash;
				}
			}
		}

		file_put_contents(
			$this->repo_dir . '/.git/classicpress-backports.json',
			json_encode(
				$this->classicpress_backports,
				JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
			)
		);
	}

	/**
	 * Do some additional processing on commits returned from $this->log():
	 *
	 * - Convert array to object
	 * - Split rawBody into subject and body (not letting git do this because
	 *   e.g. `git log 7e879f4 --pretty=format:'%f%n|%n%b%n|%n%B' -n 1` smashes
	 *   all the lines of the "subject" (%f) field together into one)
	 * - Add ->github_link (points to WP commit)
	 * - Add ->svn_id and ->trac_link (point to WP commit)
	 * - Add ->backport (points to ClassicPress incl. ->backport->github_link)
	 */
	public function parseCommitsAfterLog($commits) {
		$commits_processed = [];

		foreach ($commits as $commit_raw) {
			$commit = (object)$commit_raw;

			$parts = explode("\n", $commit->rawBody, 2);
			$commit->subject = trim($parts[0]);
			$commit->body = trim($parts[1] ?? '');

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

			$commit->body = trim(preg_replace(
				'#^git-svn-id: https://develop.svn.wordpress.org/[^@\s]*@(\d+) .*$#m',
				'',
				$commit->body
			));

			$commit->github_link = sprintf(
				'https://github.com/WordPress/wordpress-develop/commit/%s',
				$commit->commitHash
			);

			$linked = $this->linked_changesets[$commit->commitHash] ?? null;

			$backport = null;
			$backport_candidates = [$commit->commitHash];
			if ($linked) {
				$backport_candidates = array_merge(
					$backport_candidates,
					array_keys($linked['is_merged_by']),
					array_keys($linked['is_merge_of'])
				);
			}
			foreach ($backport_candidates as $c) {
				$prev_backport = $backport;
				$new_backport = $this->classicpress_backports[substr($c, 0, 10)] ?? null;
				if ($new_backport) {
					if ($prev_backport && $new_backport !== $prev_backport) {
						$this->debug(
							"Commit {$commit->commitHash} backported in both "
							. "$prev_backport and $new_backport"
						);
					}
					$backport = $new_backport;
				}
			}
			if ($backport) {
				$commit->backport = (object)[
					'github_link' => sprintf(
						'https://github.com/ClassicPress/ClassicPress/commit/%s',
						$backport
					),
					'commitHash' => $backport
				];
			}

			// Pick up commits in e.g. the trunk list that were backported to
			// the 4.9 branch before the fork point
			$commit->included_via = null;
			if ($linked) {
				$included_candidates = array_merge(
					array_keys($linked['is_merged_by']),
					array_keys($linked['is_merge_of'])
				);
				foreach ($included_candidates as $m) {
					if (isset($this->included_changesets[$m])) {
						if (isset($commit->included_via)) {
							$this->debug(
								"Commit {$commit->commitHash} included via both "
								. "{$commit->included_via->commitHash} and $m"
							);
						}
						$commit->included_via = (object)[
							'commitHash'  => $m,
							'github_link' => sprintf(
								'https://github.com/WordPress/wordpress-develop/commit/%s',
								$m
							)
						];
					}
				}
			}

			$commits_processed[] = $commit;
		}

		return $commits_processed;
	}

	private function debug($msg) {
		if (!config('app.debug')) {
			return;
		}
		error_log($msg);
	}

	public function lock() {
		$this->lock = fopen($this->repo_dir . '/.git/.lock', 'c');
		if (!flock($this->lock, LOCK_EX)) {
			fclose($this->lock);
			$this->lock = null;
			throw new ErrorException('Failed to get lock');
		}
	}

	public function unlock() {
		if (!$this->lock) {
			throw new ErrorException('Not locked');
		}
		flock($this->lock, LOCK_UN);
		fclose($this->lock);
		$this->lock = null;
	}

	public function log($revs, $all_branches=false) {
		$format = new GitLogParser\Format([
			'commitHash',
			'authorName',
			'authorEmail',
			'authorDateISO8601',
			'committerName',
			'committerEmail',
			'committerDateISO8601',
			'rawBody',
		]);
		$parser = new GitLogParser\Parser($format);
		if ($all_branches) {
			$parser->getCommand()->addArgument(new Argument('--all'));
		}
		$parser->setGitDir($this->repo_dir);
		$parser->setBranch($revs);
		return $parser->parse();
	}

	/**
	 * Run an arbitrary git command.
	 *
	 * @return TQ\Vcs\Cli\CallResult (teqneers/php-stream-wrapper-for-git)
	 */
	public function run($command, ...$arguments) {
		$call = $this->git->getGit()->createCall(
			$this->git->getRepositoryPath(),
			$command,
			$arguments
		);
		return $call->execute(null);
	}
}
