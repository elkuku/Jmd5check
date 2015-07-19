<?php
/**
 * Created by PhpStorm.
 * User: elkuku
 * Date: 17.07.15
 *
 * @copyright  (C) 2015 elkuku
 * @license    GPL http://gpl.org
 */

namespace Elkuku\Md5Check;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

defined('DS') || define('DS', DIRECTORY_SEPARATOR);

/**
 * Class Md5Check
 *
 * @since  0.1
 */
class Md5Check
{
	/**
	 * Create a MD5 checksum file.
	 *
	 * @param   string   $directory  The base difectory.
	 * @param   boolean  $compress   Compress the md5 file.
	 *
	 * @return string
	 */
	public function createMD5string($directory, $compress = true)
	{
		$lines = [];
		$md5string = '';

		$contents = (new Filesystem(new Local($directory)))
			->listContents('', true);

		foreach ($contents as $entry)
		{
			if ('file' != $entry['type'])
			{
				continue;
			}

			$md5 = md5_file($directory . '/' . $entry['path']);

			if ($compress)
			{
				$path = $entry['path'];
				$parts = explode(DS, $path);
				$fName = array_pop($parts);
				$path = implode('/', $parts);

				$lines[] = $md5 . ' ' . $this->compressPath($path) . '@' . $fName;
			}
			else
			{
				$lines[] = $md5 . ' ' . $entry['path'];
			}

			$md5string = implode("\n", $lines);

			// Windows..
			$md5string = str_replace('\\', '/', $md5string);
		}

		return $md5string;
	}

	/**
	 * Checks an extension with a given MD5 checksum file.
	 *
	 * @param   string  $path            Path to md5 file
	 * @param   array   $extensionPaths  Indexed array: First folder in md5 file path as key - extension path as value
	 *
	 * @return array Array of errors
	 */
	public function checkMD5File($path, $extensionPaths)
	{
		$lines = file($path);

		$errors = array();

		$errors[0] = 0;

		foreach ($lines as $line)
		{
			if ( ! trim($line))
			{
				continue;
			}

			list($md5, $subPath) = explode(' ', $line);

			$pos = strpos($subPath, '@');

			$path = $subPath;

			// Lines containing a @ must be compressed..
			if ($pos !== false)
			{
				$compressed = substr($subPath, 0, $pos);
				$file = substr($subPath, $pos + 1);
				$path = $this->decompress($compressed) . '/' . $file;
			}

			$parts = explode(DS, $path);

			if ( ! array_key_exists($parts[0], $extensionPaths))
			{
				continue;
			}

			$path = $extensionPaths[$parts[0]] . DS . substr($path, strlen($parts[0]) + 1);

			echo JDEBUG ? $path . '...' : '';

			$errors[0] ++;

			if ( ! file_exists($path))
			{
				$errors[] = sprintf('File not found: %s', $path);
				echo JDEBUG ? 'not found<br />' : '';

				continue;
			}

			if (md5_file($path) != $md5)
			{
				$errors[] = sprintf('MD5 check failed on file: %s', $path);
				echo JDEBUG ? 'md5 check failed<br />' : '';

				continue;
			}

			echo JDEBUG ? 'OK<br />' : '';
		}

		return $errors;
	}

	/**
	 * Tiny compression for MD5 files.
	 *
	 * @param   string  $path  The path to compress.
	 *
	 * @return string
	 */
	private function compressPath($path)
	{
		static $previous = '';

		// Init
		if ('' == $previous)
		{
			$previous = $path;

			return $previous;
		}

		// Same as previous path - maximun compression :)
		$compressed = '=';

		if ($previous != $path)
		{
			// Different path - too bad..
			$subParts = explode(DS, $path);

			// One element at Root level
			$compressed = $path;

			// More elements...
			if (count($subParts) > 1)
			{
				$previousParts = explode(DS, $previous);

				$result = array();

				$foundDifference = false;

				foreach ($subParts as $i => $part)
				{
					if (isset($previousParts[$i])
						&& $part == $previousParts[$i]
						&& ! $foundDifference)
					{
						// Same as previous sub path
						$result[] = '-';
					}
					else
					{
						// Different sub path

						if (count($result) && $result[count($result) - 1] == '-')
						{
							// Add a separator
							$result[] = '|';
						}

						$result[] = $part . DS;

						$foundDifference = true;
					}
				}

				// Add a separator(no add path)
				if (count($result) && $result[count($result) - 1] == '-')
				{
					$result[] = '|';
				}

				$compressed = implode('', $result);
			}
		}

		$previous = $path;

		return $compressed;
	}

	/**
	 * Decompress a KuKuKompress compressed path
	 *
	 * @param   string  $path  The compressed path.
	 *
	 * @return string decompressed path
	 */
	private function decompress($path)
	{
		static $previous = '';

		if ( ! $previous)
		{
			// Init
			$previous = $path;

			return $previous;
		}

		// Same as previous path - maximun compression :)
		$decompressed = $previous;

		if ($path != '=')
		{
			// Different path - too bad..

			// Separates previous path info from new path
			$pos = strpos($path, '|');

			if ($pos)
			{
				$command = substr($path, 0, $pos);

				$c = count(explode('-', $command)) - 1;

				$parts = explode('/', $previous);

				$decompressed = '';

				for ($i = 0; $i < $c; $i++)
				{
					$decompressed .= $parts[$i] . '/';
				}

				$addPath = substr($path, $pos + 1);

				$decompressed .= $addPath;

				$decompressed = trim($decompressed, '/');

				$previous = $decompressed;

				return $decompressed;
			}

			$decompressed = $path;
		}

		$decompressed = trim($decompressed, '/');

		$previous = $decompressed;

		return $decompressed;
	}
}
