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
				echo(file_get_contents($fileName));

				exit;
			}

			$body[] = 'Invalid version supplied....';
		}

		$files = scandir($hashesPath);

		$body[] = '<ul>';

		foreach ($files as $file)
		{
			if (in_array($file, ['.', '..']))
			{
				continue;
			}

			$version = preg_replace('/_hashes.txt/', '', $file);

			$body[] = '<li><a href="index.php?getVersion=' . $version . '">' . $version . '</a></li>';
		}

		$body[] = '</ul>';

		$template = file_get_contents(JPATH_ROOT . '/www/template.html');

		$this->setBody(str_replace('####CONTENT####', implode("\n", $body), $template));
	}
}
