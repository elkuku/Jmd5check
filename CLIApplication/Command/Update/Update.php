<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace CLIApplication\Command\Update;

use CLIApplication\Command\Command;

use Elkuku\Filesystem\Downloader;
use Elkuku\Md5Check\Md5Check;

use Joomla\Github\Github;

use League\Flysystem\Adapter\Local;
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

	private $deleteFiles = false;

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function execute()
	{
		$input = $this->getApplication()->input;

		$this->deleteFiles = $input->get('delete') ? true : false;
		$this->outPath = JPATH_ROOT . '/build';

		if(!is_dir($this->outPath))
		{
			throw new \UnexpectedValueException('The output directory does not exist!');
		}

		$this->checkDirs(['hashes', 'sources', 'zips']);

		$this->out('OutDir set to: ' . $this->outPath)
			->out('Fetching Joomla! releases from GitHub ... ', false)
			->getReleases()
			->out(sprintf('found <b>%d</b> releases.', count($this->releases)))
			->out('Processing releases')
			->processReleases();
	}

	private function checkDirs(array $directories)
	{
		$filesystem = new Filesystem(new Local($this->outPath));

		foreach ($directories as $directory)
		{
			if (false == $filesystem->has($directory))
			{
				$filesystem->createDir($directory);
			}
		}
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
					if (strpos($asset->name, 'Full') && $asset->content_type == 'application/zip')
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

		$downloader = new Downloader;

		foreach ($this->releases as $relNo => $url)
		{
			$this->out('Processing ' . $relNo . ' ... ', false);

			if (file_exists($this->outPath . '/hashes/' . $relNo . '_hashes.txt'))
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
				->out('cleanup ...', false)
				->cleanup()
				->out('ok');
		}

		return $this;
	}

	private function cleanup()
	{
		if (false == $this->deleteFiles)
		{
			return $this;
		}

		$filesystem = new Filesystem(new Local($this->outPath));

		$filesystem->deleteDir('zips');
		$filesystem->createDir('zips');
		$filesystem->deleteDir('sources');
		$filesystem->createDir('sources');

		return $this;
	}

	private function unpackRelease($file, $destPath)
	{
		// It's a tar.gz
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

	public function makeHashFile($dir, $filePath)
	{
		$fileContents = (new Md5Check)->createMD5string($dir);

		(new Filesystem(new Local(dirname($filePath))))
			->write(basename($filePath), $fileContents);

		return $this;
	}
}
