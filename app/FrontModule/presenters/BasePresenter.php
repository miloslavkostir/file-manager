<?php

namespace FrontModule;

use \Nette\Config\NeonAdapter;

abstract class BasePresenter extends \BasePresenter
{
	protected function startup()
	{
		parent::startup();
                $progress = NeonAdapter::load($this->context->params["appDir"] . "/storage/install.neon");
                if (!$progress['finished'])
                    $this->redirect(':Install:Homepage:');
	}

	protected function createComponentSignOutForm()
	{
		$form = new \Nette\Application\UI\Form;

		$form->addSubmit('signout', 'Sign out');

		$form->onSuccess[] = callback($this, 'signOutFormSubmitted');
		return $form;
	}

	public function signOutFormSubmitted($form)
	{
		$this->getUser()->logout();
		$this->redirect('Sign:');
	}
}