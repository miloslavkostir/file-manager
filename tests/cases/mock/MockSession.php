<?php

class MockSession extends Nette\Http\Session
{
	public $testSection;

	public function __construct() {}

	public function getSection($section, $class = 'Nette\Http\SessionSection')
	{
		return $this->testSection;
	}
}