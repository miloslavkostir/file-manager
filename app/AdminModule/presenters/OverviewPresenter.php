<?php

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

        public function renderDefault()
        {
                $messages = array();

                if (!$this->context->parameters["security"]["salt"])
                    $messages[] = "Salt hash is not set. We recommend you set up your secret hash in <a href='" . $this->link('Settings:configuration') . "'>server configuration</a> at first.";

                if (!$this->models->UserModel->getUser($this->user->id)->password)
                    $messages[] = "Your profile is not secured, because you have empty password. You can setup a new password <a href='" . $this->link('Settings:profile') . "'>here</a>.";

                if ($messages)
                    $this->template->messages = $messages;
        }
}