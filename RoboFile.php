<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Finder\Finder;

class RoboFile extends Robo\Tasks
{
	public function release()
	{
		$times = [time()];

		// On master, new commits == new patch version
		if ( $this->getBranch() == 'master' ) {
			$this->taskSemVer(__DIR__ . '/.semver')->increment('patch')->run();

			$this->taskExec('git add .')->run();
			$this->taskExec(
				'git commit -m "Preparing to release '
				. (string) $this->taskSemVer(__DIR__ . '/.semver')
				. '"'
			)->run();

			$this->taskExec('git push origin master --tags')->run();

			$times[] = time();
		}

		$file = $this->makeBundle();

		$times[] = time();

		if ( $this->getBranch() == 'master' ) {
			$this->taskExec(
				"git tag -a "
				. ((string) $this->taskSemVer(__DIR__ . '/.semver'))
				. " -m 'Releasing "
				. ((string) $this->taskSemVer(__DIR__ . '/.semver')) . "'"
			)->run();


			$this->taskExec("git push origin master --tags")->run();

			$this->uploadRelease($file);

			$times[] = time();
		}

		$this->_deleteDir('tmp');

		if ( count($times) < 3 ) {
			$this->say("Built in " . ($times[1] - $times[0]) . " s \n\n");
		} else {
			$this->say("Version bump in " . ($times[1] - $times[0]) . " s \n");

			$this->say("Built in " . ($times[2] - $times[1]) . " s \n");

			$this->say("Uploaded in " . ($times[3] - $times[2]) . " s \n");

			$this->say("Total Build time " . ($times[3] - $times[0]) . " s \n\n");
		}
	}

	public function makeBundle()
	{
		if ( is_dir(__DIR__ . '/tmp') ) {
			$this->_deleteDir('tmp');
		}

		//$this->taskExec('mkdir tmp')->run();
		$this->taskFilesystemStack()->mkdir('tmp')->run();

		$this->_mirrorDir('newsrc/code', 'tmp');

		$this->extractDir('modules');

		$this->extractDir('plugins');

        $filename = 'aec-'
            . $this->getVersion()
            . '-' . str_replace('/', '-', $this->getBranch() )
            . '-' . $this->getHash()
            . '.zip';

		$this->taskExec('zip -r ' . $filename . ' .')
			->dir('tmp')
			->printed(false)
			->run();

        return __DIR__ . '/tmp/' . $filename;
	}

	public function extractDir($dir)
	{
		foreach ( glob(__DIR__ . '/tmp/' . $dir  . '/*') as $file ) {
			$this->taskFilesystemStack()->rename(
				$file,
				str_replace('/' . $dir, '', $file)
			)->run();
		}

		$this->_deleteDir('tmp/' . $dir);
	}

	public function versionBumpClass()
	{
		$this->replaceInFile(
			__DIR__ . '/newsrc/code/components/com_acctexp/acctexp.class.php',
			"define( '_AEC_REVISION', '",
			$this->revisionGet()
		);
	}

	public function replaceInFile( $path, $search, $count )
	{
		$content = file_get_contents( $path );

		$start = strpos( $content, $search ) + strlen($search);

		$delim = substr( $search, -1, 1 );

		$length = strpos( $content, $delim, $start ) - $start;

		$old = substr( $content, $start, $length );

		if ( $old != $count ) {
			$content = str_replace(
				$search . $old . $delim,
				$search . $count . $delim,
				$content
			);

			return file_put_contents( $path, $content );
		} else {
			return null;
		}
	}

	public function getVersion()
	{
		return
			((string) $this->taskSemVer(__DIR__ . '/.semver'))
			. '-rev' . $this->revisionGet();
	}

	public function getBranch()
	{
		return trim(
			(string) $this->taskExec('git rev-parse --abbrev-ref HEAD')->run()->getMessage()
		);
	}

	public function getHash()
	{
		return trim(
			(string) $this->taskExec("git log --pretty=format:'%h' -n 1")->run()->getMessage()
		);
	}

	public function revisionGet()
	{
		// And HE told me the count of the git
		$count = trim( (string) $this->taskExec('git rev-list HEAD --count')->run()->getMessage() );

		// And on the thirteenth day of the fourth month of the year two thousand and thirteen,
		// The hardware counter stood at six thousand one hundred and two,
		// Yet THE COMPUTER spoke of four thousand eight hundred and ninety eight,
		// Thus it was deemed that the difference shall be one thousand two hundred and four
		// One Thousand two hundred and four shall be the number, no more, no less
		// Not one thousand two hundred and three, nor one thousand two hundred and five

		$count += 1204;

		return $count;
	}

	public function uploadRelease( $file )
	{
		require_once(__DIR__ . '/vendor/tan-tan-kanarek/github-php-client/client/GitHubClient.php');

		$client = new GitHubClient();
		$client->setDebug(true);
		$client->setAuthType(GitHubClient::GITHUB_AUTH_TYPE_BASIC);
		$client->setCredentials(
			'daviddeutsch',
			trim( file_get_contents(__DIR__ . '/../github-oauth.token') )
		);

		$release = $client->repos->releases->create(
			'valanx',
			'aec',
			((string) $this->taskSemVer(__DIR__ . '/.semver'))
		);

		/**
		 * TODO: This sometimes produces a
		 *
		 * 422 Unprocessable Entity
		 *
		 * Probably need to try, wait, retry until the ->create call works
		 */

		$client->repos->releases->assets->upload(
			'valanx',
			'aec',
			$release->getId(),
			basename($file),
			'application/zip',
			$file
		);
	}

	public function reuploadRelease()
	{
		require_once(__DIR__ . '/vendor/tan-tan-kanarek/github-php-client/client/GitHubClient.php');

		$client = new GitHubClient();
		$client->setDebug(true);
		$client->setAuthType(GitHubClient::GITHUB_AUTH_TYPE_BASIC);
		$client->setCredentials(
			'daviddeutsch',
			trim( file_get_contents(__DIR__ . '/../github-oauth.token') )
		);

		foreach ( glob(__DIR__ . '/tmp/*.zip') as $path ) {
			$file = $path;
		}

		$release = $client->repos->releases->get(
			'valanx',
			'aec',
			'latest'
		);

		$client->repos->releases->assets->upload(
			'valanx',
			'aec',
			$release->getId(),
			basename($file),
			'application/zip',
			$file
		);
	}
}
