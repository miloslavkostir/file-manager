<?php

namespace AdminModule;

class UsersPresenter extends BasePresenter
{
	protected function startup()
	{
		parent::startup();

		if (!$this->user->isLoggedIn())
			$this->redirect('Sign:');
	}
        
        protected function createComponentUsers()
        {
            $users = new \UsersControl;
            return $users;
        }
}