#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: elkuku
 * Date: 14.07.15
 * Time: 11:46
 */

use Filesystem\Downloader;
use Application\Application;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Filesystem;

'cli' == PHP_SAPI
|| die("\nThis script must be run from the command line interface.\n\n");

// Configure error reporting to maximum for CLI output.
error_reporting(-1);
ini_set('display_errors', 1);

define('JPATH_ROOT', __DIR__);


// Load the autoloader
$path = realpath(JPATH_ROOT . '/vendor/autoload.php');

if (!$path)
{
	// Do not translate!
	echo 'ERROR: Composer not properly set up! Run "composer install" or see README.md for more details.' . PHP_EOL;

	exit(1);
}

$loader = include $path;

// Add the namespace for our application to the autoloader.
/* @type Composer\Autoload\ClassLoader $loader */
$loader->add('Application', __DIR__);
$loader->add('Filesystem', __DIR__);

try
{
	(new Application)->execute();
}
catch (\Exception $e)
{
	$trace = $e->getTraceAsString();

	if (function_exists('g11n3t'))
	{
		echo "\n\n"
			. sprintf(g11n3t('ERROR: %s'), $e->getMessage())
			. "\n\n"
			. g11n3t('Call stack:') . "\n"
			. str_replace(JPATH_ROOT, 'JPATH_ROOT', $e->getTraceAsString());
	}
	else
	{
		// The language library has not been loaded yet :(
		echo "\n\n"
			. 'ERROR: ' . $e->getMessage()
			. "\n\n"
			. 'Call stack:' . "\n"
			. str_replace(JPATH_ROOT, 'JPATH_ROOT', $e->getTraceAsString());
	}

	exit($e->getCode() ? : 255);
}

exit;




exit;
$checker = new Application;

$releases = $checker->getJoomlaCmsReleases();

$outDir = __DIR__ . '/testhashes';

$fileSystem = new Filesystem(new NullAdapter($outDir));

$downloader = new Downloader($fileSystem);

foreach ($releases as $relNo => $url)
{
	if (file_exists($outDir . '/' . basename($url)))
	{
		continue;
	}
exit;
	$downloader->download($url, $outDir);


////	$fname = basename($url);
	//$fileSystem->copy($url, $outDir . '/' . $fname);
}


exit;
$dir = __DIR__ . '/test';
$checker->printDir($dir);

$hashes = $checker->getHashes($dir);

print_r($hashes);

$checker->makeHashFile($dir, __DIR__ . '/testhashes/test.txt');
