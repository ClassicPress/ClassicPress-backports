<?php

namespace App;

use Tivie\GitLogParser;
use TQ\Git\Repository\Repository;

class GitRepository {
	private $git = null;
	private $repo_dir = null;

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
	}

	public function fetchIfStale($threshold = 10*60) {
		$last_fetch = @filemtime($this->repo_dir . '/.git/FETCH_HEAD');
		if (time() - $last_fetch > 10*60) {
			foreach (['origin', 'upstream'] as $remote) {
				$this->run('fetch', $remote)
					->assertSuccess("Failed to fetch remote $remote");
			}
		}
	}

	public function lock() {
		$this->f = fopen($this->repo_dir . '/.git/.lock', 'c');
		if (!flock($this->f, LOCK_EX)) {
			fclose($this->f);
			$this->f = null;
			throw new ErrorException('Failed to get lock');
		}
	}

	public function unlock() {
		if (!$this->f) {
			throw new ErrorException('Not locked');
		}
		flock($this->f, LOCK_UN);
		fclose($this->f);
		$this->f = null;
	}

	public function log($revs) {
        $format = new GitLogParser\Format([
            'commitHash',
            'authorName',
            'authorEmail',
            'authorDateISO8601',
            'committerName',
            'committerEmail',
            'committerDateISO8601',
            'subject',
            'body',
        ]);
        $parser = new GitLogParser\Parser($format);
		$parser->setGitDir($this->repo_dir);
        $parser->setBranch($revs);
		return $parser->parse();
	}

	public function run($command, ...$arguments) {
		$call = $this->git->getGit()->createCall(
			$this->git->getRepositoryPath(),
			$command,
			$arguments
		);
		return $call->execute(null);
	}
}