<?php

namespace AdminModule;

class UsersPresenter extends BasePresenter
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
        
        protected function createComponentUsers()
        {
            $users = new \UsersControl;
            return $users;
        }
}