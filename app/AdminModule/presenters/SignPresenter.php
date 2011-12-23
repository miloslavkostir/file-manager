<?php

namespace AdminModule;

use Nette\Application\UI\Form,
        Nette\Security as NS;

class SignPresenter extends BasePresenter
{
	/** @persistent */
        public $backlink;

	protected function createComponentSignInForm()
	{
		$form = new Form;
		$form->addText('username', 'Username:')
			->setRequired('Please provide a username.');

		$form->addPassword('password', 'Password:');

		$form->addCheckbox('remember', 'Remember this computer');

		$form->addSubmit('send', 'Sign in')
                        ->setAttribute("class", "ui-button ui-button-text-only ui-widget ui-state-default ui-corner-all");

		$form->onSuccess[] = callback($this, 'signInFormSubmitted');
		return $form;
	}

	public function signInFormSubmitted($form)
	{
		try {
			$values = $form->values;
			if ($values->remember)
				$this->getUser()->setExpiration('+ 14 days', FALSE);
			else
				$this->getUser()->setExpiration('+ 1 minutes', TRUE);

			$this->getUser()->login($values->username, $values->password);

                        $module = preg_replace("#:?[a-zA-Z_0-9]+$#", "", $this->getName());
                        if (!$this->user->isAllowed($module))
                            throw new NS\AuthenticationException("User '$values->username' is not allowed to enter here.");

			$this->application->restoreRequest($this->backlink);
			$this->redirect("Dashboard:");

		} catch (NS\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}

        public function actionOut()
        {
		$this->getUser()->logout();
		$this->redirect('Sign:');
        }
}