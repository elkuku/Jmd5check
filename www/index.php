<?php

use WebApplication\Application;

// Set error reporting for development
error_reporting(-1);

// Define required paths
define('JPATH_ROOT', dirname(__DIR__));
define('JPATH_CONFIGURATION', JPATH_ROOT . '/etc');

// Load the Composer autoloader
$path = realpath(JPATH_ROOT . '/vendor/autoload.php');

if (!$path)
{
	header('HTTP/1.1 500 Internal Server Error', null, 500);
	echo 'ERROR: Composer not properly set up! Run "composer install" or see README.md for more details' . PHP_EOL;

	exit(1);
}

include $path;

// Execute the application.
try
{
	(new Application)
		->execute();
}
catch (\Exception $e)
{
	header('HTTP/1.1 500 Internal Server Error', null, 500);
	echo 'Error instantiating the application - ' . $e->getMessage();
}
