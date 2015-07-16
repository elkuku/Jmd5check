<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace CLIApplication\Command\Update;

use CLIApplication\Command\Command;

use Filesystem\Downloader;
use Joomla\Github\Github;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Filesystem;

/**
 * Class for displaying help data for the installer application.
 *
 * @since  1.0
 */
class Update extends Command
{
	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = 'Update the whole stuff.';

	private $releases = [];

	private $outPath = '';

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$this->outPath = $this->getApplication()->input->getPath('o');

		if (!$this->outPath)
		{
			throw new \UnexpectedValueException('Please specify an output directory using the -o option');
		}

		$this->out('OutDir set to: ' . $this->outPath)
			->out('Fetching Joomla! releases from GitHub ... ', false)
			->getReleases()
			->out(sprintf('found <b>%d</b> releases.', count($this->releases)))
			->out('Processing releases')
			->processReleases();
	}

	public function getReleases()
	{
		$gitHub = new Github();

		// Hey this will only get the FIRST PAGE... that should be OK.
		$releases = $gitHub->repositories->releases->getList('joomla', 'joomla-cms');

		foreach ($releases as $relNo => $release)
		{
			$relUri = '';

			if (true == $release->prerelease)
			{
				// Only stables
				continue;
			}

			if (!$release->assets)
			{
				// Screw ya...
				continue;
			}

			foreach ($release->assets as $asset)
			{
				if (
					strpos($asset->name, 'Full')
					&& $asset->content_type == 'application/x-gzip'
					|| $asset->content_type == 'application/gzip'
				)
				{
					$relUri = $asset->browser_download_url;

					break;
				}
			}

			if (!$relUri)
			{
				// OK... SO... The Joomla! Project (TM) seems to have uploaded only ZIPs for *some* versions.....

				foreach ($release->assets as $asset)
				{
					if (
						strpos($asset->name, 'Full')
						&& $asset->content_type == 'application/zip'
					)
					{
						$relUri = $asset->browser_download_url;

						break;
					}
				}
			}

			if (!$relUri)
			{
				throw new \RuntimeException('No suitable download found for release ' . $relNo);
			}

			$this->releases[$relNo] = $relUri;
		}

		ksort($this->releases);

		return $this;
	}

	private function processReleases()
	{
		$downloadDir = $this->outPath . '/zips';

		$downloader = new Downloader(new Filesystem(new NullAdapter($downloadDir)));

		foreach ($this->releases as $relNo => $url)
		{
			$this->out('Processing ' . $relNo . ' ... ', false);

			if (file_exists($downloadDir . '/' . basename($url)))
			{
				$this->out('already downloaded.');

				continue;
			}

			$this->out('download ... ', false);

			$downloader->download($url, $downloadDir);

			$extractDir = $this->outPath . '/sources/' . $relNo;

			$this->out('unpack ... ', false)
				->unpackRelease($downloadDir . '/' . basename($url), $extractDir)
				->out('create hash file ... ', false)
				->makeHashFile($extractDir, $this->outPath . '/hashes/' . $relNo . '_hashes.txt')
				->out('ok');
		}

		return $this;
	}

	private function unpackRelease($file, $destPath)
	{
		// It's a tag.gz
		$pharData = new \PharData($file);
		//$pharData->decompress();

		//$pharData = new PharData(pathinfo($outDir . '/' . $fName, PATHINFO_FILENAME));
		$pharData->extractTo($destPath);

		// It's a ZIP :(
		if (false)
		{
			$zip = new \ZipArchive;

			$outDir = __DIR__ . '/testhashes';

			exit;
			if ($zip->open($outDir . '/' . $fName) === TRUE) {
				$zip->extractTo($outDir . '/test/');
				$zip->close();
				echo 'ok';
			} else {
				echo 'failed';
			}


		}

		return $this;
	}

	public function getHashes($dir)
	{
		$contents = (new Filesystem(new Local($dir)))
			->listContents('', true);

		$hashes = [];

		foreach ($contents as $content)
		{
			if ('file' == $content['type'])
			{
				$hashes[$content['path']] = md5_file($dir . '/' . $content['path']);
			}
		}

		return $hashes;
	}

	public function makeHashFile($dir, $filePath)
	{
		$hashes = $this->getHashes($dir);

		$lines = [];

		foreach ($hashes as $path => $hash)
		{
			$lines[] = $hash . ' ' . $path;
		}

		$fileContents = implode("\n", $lines);

		(new Filesystem(new Local(dirname($filePath))))
			->write(basename($filePath), $fileContents);

		return $this;
	}
}