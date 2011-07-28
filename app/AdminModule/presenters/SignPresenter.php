<?php

namespace AdminModule;

use Nette\Application\UI\Form,
        Nette\Security as NS;

class SignPresenter extends BasePresenter
{
	protected function createComponentSignInForm()
	{
		$form = new Form;
		$form->addText('username', 'Username:')
			->setRequired('Please provide a username.');

		$form->addPassword('password', 'Password:');

		$form->addCheckbox('remember', 'Remember this computer');

		$form->addSubmit('send', 'Sign in');

		$form->onSuccess[] = callback($this, 'signInFormSubmitted');
		return $form;
	}

	public function signInFormSubmitted($form)
	{
		try {
			$values = $form->getValues();
			if ($values->remember) {
				$this->getUser()->setExpiration('+ 14 days', FALSE);
			} else {
				$this->getUser()->setExpiration('+ 20 minutes', TRUE);
			}
			$this->getUser()->login($values->username, $values->password);
			$this->redirect('Overview:');

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