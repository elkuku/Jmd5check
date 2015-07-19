<?php
/**
 * Part of the Joomla! Tracker application.
 *
 * @copyright  Copyright (C) 2012 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License Version 2 or Later
 */

namespace CLIApplication\Command;

use Joomla\DI\ContainerAwareInterface;
use Joomla\DI\ContainerAwareTrait;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * TrackerCommand class
 *
 * @since  1.0
 */
abstract class Command implements LoggerAwareInterface, ContainerAwareInterface
{
	use LoggerAwareTrait;
	use ContainerAwareTrait;

	/**
	 * Array of options.
	 *
	 * @var    array
	 * @since  1.0
	 */
	protected $options = array();

	/**
	 * The command "description" used for help texts.
	 *
	 * @var    string
	 * @since  1.0
	 */
	protected $description = '';

	/**
	 * Use the progress bar.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $usePBar;

	/**
	 * Execute the command.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	abstract public function execute();

	/**
	 * Get a description text.
	 *
	 * @return  string
	 *
	 * @since   1.0
	 */
	public function getDescription()
	{
		return $this->description;
	}

	/**
	 * Add a command option.
	 *
	 * @param   CommandOption  $option  The command option.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function addOption(CommandOption $option)
	{
		$this->options[] = $option;

		return $this;
	}

	/**
	 * Write a string to standard output.
	 *
	 * @param   string   $text  The text to display.
	 * @param   boolean  $nl    True (default) to append a new line at the end of the output string.
	 *
	 * @return  $this
	 *
	 * @codeCoverageIgnore
	 * @since   1.0
	 */
	protected function out($text = '', $nl = true)
	{
		$this->getApplication()->out($text, $nl);

		return $this;
	}

	/**
	 * Pass a string to the attached logger.
	 *
	 * @param   string  $text  The text to display.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function logOut($text)
	{
		// Send text to the logger and remove color chars.
		$this->getLogger()->info(preg_replace('/\<[a-z\/]+\>/', '', $text));

		return $this;
	}

	/**
	 * Write a string to the standard output if an operation has terminated successfully.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	protected function outOK()
	{
		return $this->out('<ok>ok</ok>');
	}

	/**
	 * Display an error "page" if no options have been found for a given command.
	 *
	 * @param   string  $dir  The base directory for the commands.
	 *
	 * @return  $this
	 *
	 * @since   1.0
	 */
	public function displayMissingOption($dir)
	{
		$command = strtolower(join('', array_slice(explode('\\', get_class($this)), -1)));

		$this->getApplication()->out(sprintf('Command: %s', ucfirst($command)));

		$errorTitle1 = sprintf('Missing option for command: %s', $command);
		$errorTitle2 = ('Please use one of the following :');

		$maxLen = (strlen($errorTitle1) > strlen($errorTitle2)) ? strlen($errorTitle1) : strlen($errorTitle2);

		$filesystem = new Filesystem(new Local($dir));

		$this->out('<error>  ' . str_repeat(' ', $maxLen) . '  </error>');
		$this->out('<error>  ' . $errorTitle1 . str_repeat(' ', $maxLen - strlen($errorTitle1)) . '  </error>');
		$this->out('<error>  ' . $errorTitle2 . str_repeat(' ', $maxLen - strlen($errorTitle2)) . '  </error>');
		$this->out('<error>  ' . str_repeat(' ', $maxLen) . '  </error>');

		$files = $filesystem->listContents();
		sort($files);

		foreach ($files as $file)
		{
			$cmd = strtolower($file['filename']);

			if ('file' != $file['type'] || $command == $cmd)
			{
				// Exclude the base class
				continue;
			}

			$this->out('<error>  ' . $command . ' ' . $cmd
				. str_repeat(' ', $maxLen - strlen($cmd) - strlen($command) + 1)
				. '</error>');
		}

		$this->out('<error>  ' . str_repeat(' ', $maxLen) . '  </error>');
	}

	/**
	 * Get the application object.
	 *
	 * @return  \CLIApplication\Application
	 *
	 * @since   1.0
	 */
	protected function getApplication()
	{
		return $this->getContainer()->get('app');
	}

	/**
	 * Get the logger object.
	 *
	 * @return  \Psr\Log\LoggerInterface
	 *
	 * @since   1.0
	 */
	protected function getLogger()
	{
		return $this->getContainer()->get('logger');
	}

	/**
	 * Execute a command on the server.
	 *
	 * @param   string  $command  The command to execute.
	 *
	 * @return string
	 *
	 * @since   1.0
	 * @throws \RuntimeException
	 */
	protected function execCommand($command)
	{
		$lastLine = system($command, $status);

		if ($status)
		{
			// Command exited with a status != 0
			if ($lastLine)
			{
				$this->logOut($lastLine);

				throw new \RuntimeException($lastLine);
			}

			$this->logOut('An unknown error occurred');

			throw new \RuntimeException('An unknown error occurred');
		}

		return $lastLine;
	}
}
