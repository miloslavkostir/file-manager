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

class OverviewPresenter extends BasePresenter
{
	protected function startup()
	{
		parent::startup();

		if (!$this->user->isLoggedIn())
			$this->redirect('Sign:');
	}
}