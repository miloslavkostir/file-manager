<?php

namespace AdminModule;

class DashboardPresenter extends BasePresenter
{
	protected function startup()
	{
                parent::startup();

                $user = $this->user;
                if (!$user->isLoggedIn()) {

			if ($user->logoutReason === \Nette\Security\User::INACTIVITY)
				$this->flashMessage("You have been signed out due to inactivity. Please sign in again.");
			$backlink = $this->application->storeRequest();
                        $this->redirect("Sign:", array("backlink" => $backlink));
                }

                $module = preg_replace("#:?[a-zA-Z_0-9]+$#", "", $this->getName());
                if (!$user->isAllowed($module))
                    throw new \Nette\Application\ForbiddenRequestException();
	}

        public function renderDefault()
        {
                $messages = array();

                if (!$this->context->parameters["security"]["salt"])
                    $messages[] = "Salt hash is not set. We recommend you set up your secret hash in <a href='" . $this->link('Settings:configuration') . "'>server configuration</a> at first.";

                if (!$this->models->UserModel->getUser($this->user->id)->password)
                    $messages[] = "Your profile is not secured, because you have empty password. You can setup a new password <a href='" . $this->link('Profile:') . "'>here</a>.";

                if ($messages)
                    $this->template->messages = $messages;
        }
}