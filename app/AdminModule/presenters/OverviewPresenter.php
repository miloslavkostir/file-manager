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
                    $messages[] = "Salt hash is not set. You can setup this in <strong>app/config/config.neon</strong>.";
                if ($messages)
                    $this->template->messages = $messages;
        }
}