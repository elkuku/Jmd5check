<?php
namespace Filesystem;

/**
 * Created by PhpStorm.
 * User: elkuku
 * Date: 15.07.15
 * Time: 10:32
 */

class Downloader
{
	/**
	 * Downloads a request
	 *
	 * @param string $url
	 *
	 * @return boolean
	 */
	public function download($url, $path)
	{
		$destPath = $path . '/' . basename($url);

		if (file_exists($destPath))
		{
			throw new \RuntimeException('File does already exist.');
		}

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		// IMPORTANT !!!
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		//curl_setopt($ch, CURLOPT_SSLVERSION,3);
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);

		$data = curl_exec ($ch);
		$error = curl_error($ch);

		curl_close ($ch);

		if ($error)
		{
			throw new \DomainException($error);
		}

		$destination = basename($url);

		$file = fopen($path . '/' . $destination, "w+");

		if (!$file)
		{
			throw new \DomainException('Can not open file for download');
		}

		fputs($file, $data);
		fclose($file);

		return $this;
	}
}
