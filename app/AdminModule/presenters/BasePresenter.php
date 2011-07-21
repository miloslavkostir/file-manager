<?php

/**
 * My Application
 *
 * @copyright  Copyright (c) 2010 John Doe
 * @package    MyApplication
 */


/**
 * Base class for all application presenters.
 *
 * @author     John Doe
 * @package    MyApplication
 */
namespace AdminModule;

abstract class BasePresenter extends \BasePresenter
{
	protected function createComponentSignOutForm()
	{
		$form = new \Nette\Application\UI\Form;

		$form->addSubmit('signout', 'Sign out')
                        ->setAttribute('class', 'ui-button ui-button-text-only ui-widget ui-state-default ui-corner-all');

		$form->onSuccess[] = callback($this, 'signOutFormSubmitted');
		return $form;
	}

	public function signOutFormSubmitted($form)
	{
		$this->getUser()->logout();
		$this->redirect('Sign:');
	}
}
