<?php

namespace FrontModule;

abstract class BasePresenter extends \BasePresenter
{
	protected function startup()
	{
		parent::startup();
                $loader = new \Nette\Config\Loader;
                $progress = $loader->load(APP_DIR . "/storage/install.neon");
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