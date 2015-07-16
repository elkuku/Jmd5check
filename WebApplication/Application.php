<?php
/**
 * Created by PhpStorm.
 * User: elkuku
 * Date: 14.07.15
 * Time: 12:15
 */

namespace WebApplication;

use Joomla\Application\AbstractWebApplication;
use Joomla\Input\Input;
use Joomla\Registry\Registry;

class Application extends AbstractWebApplication
{
	/**
	 * Class constructor.
	 *
	 * @param   Input      $input   An optional argument to provide dependency injection for the application's
	 *                              input object.  If the argument is a InputCli object that object will become
	 *                              the application's input object, otherwise a default input object is created.
	 * @param   Registry   $config  An optional argument to provide dependency injection for the application's
	 *                              config object.  If the argument is a Registry object that object will become
	 *                              the application's config object, otherwise a default config object is created.
	 *
	 * @since   1.0
	 */
	public function __construct(Input $input = null, Registry $config = null)
	{
		parent::__construct($input, $config);

	}

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
		$body = 'hi';
		$this->setBody($body);

		//$this->respond();

		return;
		$this->processCommands();

		$this->out()
			->out(str_repeat('_', 40))
			->out(
				sprintf(
					'Execution time: <b>%d sec.</b>',
					time() - $this->get('execution.timestamp')
				)
			)
			->out(str_repeat('_', 40));
	}

	protected function processCommands()
	{
		$composerCfg = json_decode(file_get_contents(JPATH_ROOT . '/composer.json'));

		$this->out(sprintf('Joomla! Core md5 maker %s', $composerCfg->version));

		$args = $this->input->args;

		if (!$args || (isset($args[0]) && 'help' == $args[0]))
		{
			$command = 'help';
			$action  = 'help';
		}
		else
		{
			$command = $args[0];

			$action = (isset($args[1])) ? $args[1] : $command;
		}

		$className = 'CLIApplication\\Command\\' . ucfirst($command) . '\\' . ucfirst($action);

		if (false == class_exists($className))
		{
			$this->out()
				->out(sprintf('Invalid command: %s', '<error> ' . (($command == $action) ? $command : $command . ' ' . $action) . ' </error>'))
				->out();

			$alternatives = $this->getAlternatives($command, $action);

			if (count($alternatives))
			{
				$this->out('<b>' . 'Did you mean one of this?' . '</b>')
					->out('    <question> ' . implode(' </question>    <question> ', $alternatives) . ' </question>');

				return;
			}

			$className = 'Application\\Command\\Help\\Help';
		}

		if (false == method_exists($className, 'execute'))
		{
			throw new \RuntimeException(sprintf('Missing method %1$s::%2$s', $className, 'execute'));
		}

		try
		{
			/* @type Command $command */
			$command = new $className;

			if ($command instanceof ContainerAwareInterface)
			{
				$command->setContainer($this->container);
			}

			$command->execute();
		}
		catch (AbortException $e)
		{
			$this->out('')
				->out('<comment>' . g11n3t('Process aborted.') . '</comment>');
		}
	}

	/**
	 * Get alternatives for a not found command or action.
	 *
	 * @param   string  $command  The command.
	 * @param   string  $action   The action.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	protected function getAlternatives($command, $action)
	{
		$commands = (new Help)->setContainer($this->getContainer())->getCommands();

		$alternatives = [];

		if (false == array_key_exists($command, $commands))
		{
			// Unknown command
			foreach (array_keys($commands) as $cmd)
			{
				if (levenshtein($cmd, $command) <= strlen($cmd) / 3 || false !== strpos($cmd, $command))
				{
					$alternatives[] = $cmd;
				}
			}
		}
		else
		{
			// Known command - unknown action
			$actions = (new Help)->setContainer($this->getContainer())->getActions($command);

			foreach (array_keys($actions) as $act)
			{
				if (levenshtein($act, $action) <= strlen($act) / 3 || false !== strpos($act, $action))
				{
					$alternatives[] = $command . ' ' . $act;
				}
			}
		}

		return $alternatives;
	}

	/**
	 * Get the command options.
	 *
	 * @return  array
	 *
	 * @since   1.0
	 */
	public function getCommandOptions()
	{
		return $this->commandOptions;
	}
}
