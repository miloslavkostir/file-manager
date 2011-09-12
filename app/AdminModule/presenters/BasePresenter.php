<?php

namespace AdminModule;

use \Nette\Config\NeonAdapter;

abstract class BasePresenter extends \BasePresenter
{
	protected function startup()
	{
		parent::startup();
                $progress = NeonAdapter::load($this->context->params["appDir"] . "/storage/install.neon");
                if (!$progress['finished'])
                    $this->redirect(':Install:Homepage:');
	}
}
