<?php

use Nette\Http\Session;

class MockSession extends Session
{

    public $testSection;

    public function __construct()
    {

    }

    public function getSection($section, $class = "Nette\Http\SessionSection")
    {
        return $this->testSection;
    }

}