<?php

defined('_JEXEC') or die;

class Jmd5checkController extends JControllerLegacy
{
	public function download()
	{
		$version = $this->input->getCmd('version');

		// Version must contain only numbers and dots
		$version = preg_replace('/[^0-9.]+/', '', $version);

		$config = JComponentHelper::getParams('com_jmd5check');

		$url = $config->get('server_url') . '/?getVersion=' . $version;

		$serverUrl = $config->get('server_url');

		if (!$serverUrl)
		{
			throw new UnexpectedValueException('No server URL set in config.');
		}

		$downloadFolder = JPATH_COMPONENT_ADMINISTRATOR . '/hashes';

		$destPath = $downloadFolder . '/' . $version . '_hashes.txt';

		if (file_exists($destPath))
		{
			throw new \RuntimeException('File does already exist.');
		}

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		// IMPORTANT !!!
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		// TEST curl_setopt($ch, CURLOPT_SSLVERSION,3);
		// TEST curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);

		$data = curl_exec($ch);
		$error = curl_error($ch);

		curl_close($ch);

		if ($error)
		{
			throw new \DomainException($error);
		}

		$file = fopen($destPath, "w+");

		if (!$file)
		{
			throw new \DomainException('Can not open file for download');
		}

		fputs($file, $data);
		fclose($file);

		parent::display();

		return $this;
	}

	public function check()
	{
		jimport('joomla.filesystem.folder');

		$version = (new JVersion)->getShortVersion();

		$fileName = JPATH_COMPONENT_ADMINISTRATOR . '/hashes/' . $version . '_hashes.txt';

		$hashes = $this->getHashes($fileName);

		$files = JFolder::files(JPATH_ROOT, '.', true, true);

		$checksFailed = [];
		$checksAdd = [];

		$skipFolders = [
			'installation/',
			'/components/com_jmd5check/'
		];

		JLoader::registerNamespace('Elkuku', JPATH_LIBRARIES . '/elkuku');

		$md5Checker = new \Elkuku\Md5Check\Md5Check;

		$excludeFolders = [
			'installation'
		];

		$result = $md5Checker->checkMD5File($fileName, JPATH_ROOT, $excludeFolders);

		var_dump($result);
return;
		foreach ($files as $file)
		{
			foreach ($skipFolders as $skipFolder)
			{
				if (false !== strpos($file, $skipFolder))
				{
					continue 2;
				}
			}

			if (false == array_key_exists($file, $hashes))
			{
				// Superfluous file
				$checksAdd[] = $file;

				continue;
			}

			$md5 = md5_file($file);

			if ($md5 != $hashes[$file])
			{
				// The md5 check failed.
				$checksFailed[] = $file;
				unset($hashes[$file]);

				continue;
			}

			// The file is "all right".
			unset($hashes[$file]);
		}

		foreach ($hashes as $file => $hash)
		{
			foreach ($skipFolders as $skipFolder)
			{
				if (false !== strpos($file, $skipFolder))
				{
					continue 2;
				}
			}

			$checksMissing[] = $file;
		}

		$view = $this->getView('jmd5check', 'html');

		$view->set('checksFailed', $checksFailed);
		$view->set('checksMissing', $checksMissing);
		$view->set('checksAdd', $checksAdd);

		$view->setLayout('result');

		parent::display();
	}

	private function getHashes($fileName)
	{
		if (false == file_exists($fileName))
		{
			throw new UnexpectedValueException('Hash file not found.');
		}

		$hashes = [];
		$lines = file($fileName);

		foreach ($lines as $line)
		{
			$parts = explode(' ', $line);

			if (2 != count($parts))
			{
				throw new UnexpectedValueException('Invalid hash file');
			}

			$hashes[JPATH_ROOT . '/' . trim($parts[1])] = $parts[0];
		}

		return $hashes;
	}
}
