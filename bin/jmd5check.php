#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: elkuku
 * Date: 14.07.15
 * Time: 11:46
 */

use CLIApplication\Application;

'cli' == PHP_SAPI
|| die("\nThis script must be run from the command line interface.\n\n");

// Configure error reporting to maximum for CLI output.
error_reporting(-1);
ini_set('display_errors', 1);

define('JPATH_ROOT', realpath(__DIR__ . '/..'));

// Load the autoloader
$path = realpath(JPATH_ROOT . '/vendor/autoload.php');

if (!$path)
{
	// Do not translate!
	echo 'ERROR: Composer not properly set up! Run "composer install" or see README.md for more details.' . PHP_EOL;

	exit(1);
}

include $path;

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
