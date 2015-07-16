<?php

defined('_JEXEC') or die;

class Jmd5checkViewJmd5check extends JViewLegacy
{
	protected $version = '';

	protected $hashFile = '';

	protected $downloadLink = '';

	protected $checkLink = '';

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  void
	 */
	public function display($tpl = null)
	{
		JToolbarHelper::title('Jmd5Check');
		JToolbarHelper::help('nope...');
		JToolbarHelper::preferences('com_jmd5check');

		$this->version = (new JVersion)->getShortVersion();

		$this->downloadLink = 'index.php?option=com_jmd5check&task=download&version=' . $this->version;
		$this->checkLink = 'index.php?option=com_jmd5check&task=check';

		$fileName = JPATH_COMPONENT_ADMINISTRATOR . '/hashes/' . $this->version . '_hashes.txt';

		if (file_exists($fileName))
		{
			$this->hashFile = $fileName;
		}

		parent::display($tpl);
	}
}
