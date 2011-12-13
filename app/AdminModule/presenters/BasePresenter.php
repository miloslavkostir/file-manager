<?php

namespace AdminModule;

abstract class BasePresenter extends \BasePresenter
{
	protected function startup()
	{
		parent::startup();
                $loader = new \Nette\Config\Loader;
                $progress = $loader->load(APP_DIR . "/storage/install.neon");
                if (!$progress['finished'])
                    $this->redirect(':Install:Homepage:');
	}
}
