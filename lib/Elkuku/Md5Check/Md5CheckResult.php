<?php
/**
 * Created by PhpStorm.
 * User: elkuku
 * Date: 19.07.15
 * Time: 12:53
 */

namespace Elkuku\Md5Check;

class Md5CheckResult
{
	public $filesChecked = 0;

	public $checkErrors = [];

	public $missingFiles = [];

	public $addedFiles = [];

	public function addCheckError($path)
	{
		$this->checkErrors[] = $path;
	}
	public function addMissingFile($path)
	{
		$this->missingFiles[] = $path;
	}
	public function addAddedFile($path)
	{
		$this->addedFiles[] = $path;
	}
}
