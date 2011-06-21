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

		// user authentication
		if (!$this->user->isLoggedIn()) {
			if ($this->user->logoutReason === \Nette\Http\User::INACTIVITY) {
				$this->flashMessage('You have been signed out due to inactivity. Please sign in again.');
			}
			$backlink = $this->application->storeRequest();
			$this->redirect('Sign:', array('backlink' => $backlink));
		}
	}
}