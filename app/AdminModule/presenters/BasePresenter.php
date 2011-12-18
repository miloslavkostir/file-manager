<?php

namespace AdminModule;

abstract class BasePresenter extends \BasePresenter
{
	protected function startup()
	{
		parent::startup();
                if (!$this->context->parameters["install"]["finished"])
                    $this->redirect(':Install:Homepage:');
	}
}
