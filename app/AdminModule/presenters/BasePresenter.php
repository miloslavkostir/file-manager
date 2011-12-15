<?php

namespace AdminModule;

abstract class BasePresenter extends \BasePresenter
{
	protected function startup()
	{
		parent::startup();
                $loader = new \Nette\Config\Loader;
                $progress = $loader->load($this->context->parameters["confDir"] . "/install.neon");
                if (!$progress['finished'])
                    $this->redirect(':Install:Homepage:');
	}
}
