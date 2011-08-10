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

                $user = $this->user;

		if (!$user->isLoggedIn())
			$this->redirect('Sign:');

                $module = preg_replace("#:?[a-zA-Z_0-9]+$#", "", $this->getName());
                if (!$user->isAllowed($module))
                    throw new \Nette\Application\ForbiddenRequestException();
	}
}