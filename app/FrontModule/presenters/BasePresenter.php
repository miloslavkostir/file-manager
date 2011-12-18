<?php

namespace FrontModule;

abstract class BasePresenter extends \BasePresenter
{
	protected function startup()
	{
		parent::startup();
                if (!$this->context->parameters["install"]["finished"])
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