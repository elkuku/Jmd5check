<?php
/**
 * Created by PhpStorm.
 * User: elkuku
 * Date: 14.07.15
 * Time: 12:15
 */

namespace WebApplication;

use Joomla\Application\AbstractWebApplication;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class Application extends AbstractWebApplication
{
	/**
	 * Method to run the application routines.  Most likely you will want to instantiate a controller
	 * and execute it, or perform some sort of task directly.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function doExecute()
	{
		$version = $this->input->getCmd('getVersion');

		$hashesPath = JPATH_ROOT . '/build/hashes';

		$body = [];

		if ($version)
		{
			$fileName = $hashesPath . '/' . $version . '_hashes.txt';

			if (file_exists($fileName))
			{
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename=' . basename($fileName));
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($fileName));

				readfile($fileName);

				exit;
			}

			$body[] = '<div class="alert alert-warning">Invalid version supplied....</div>';
		}

		if (is_dir($hashesPath))
		{
			$files = scandir($hashesPath);
		}
		else
		{
			$files = [];
			$body[] = '<div class="alert alert-danger"><strong>No hashes found.</strong> Please run update!</div>';
		}

		$body[] = '<ul class="list-unstyled">';

		foreach ($files as $file)
		{
			if (in_array($file, ['.', '..']))
			{
				continue;
			}

			$version = preg_replace('/_hashes.txt/', '', $file);

			$body[] = '<li><a href="?getVersion=' . $version . '">' . $version . '</a></li>';
		}

		$body[] = '</ul>';

		$template = file_get_contents(JPATH_ROOT . '/www/template.html');
		$template = str_replace('####CONTENT####', implode("\n", $body), $template);

		$this->setBody($template);
	}
}
