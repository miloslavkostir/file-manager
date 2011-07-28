<?php

/**
 * My Application
 *
 * @copyright  Copyright (c) 2010 John Doe
 * @package    MyApplication
 */



/**
 * Homepage presenter.
 *
 * @author     John Doe
 * @package    MyApplication
 */

namespace AdminModule;

class SettingsPresenter extends BasePresenter
{
	protected function startup()
	{
		parent::startup();

		if (!$this->user->isLoggedIn())
			$this->redirect('Sign:');
	}

        protected function createComponentRoots()
        {
                $root = new \RootControl;
                return $root;
        }

	protected function createComponentProfile()
	{
		$profile = new \ProfileControl;
		return $profile;
	}
}