<?php

namespace FrontModule;

use Nette\Application\UI\Form,
        Nette\Security as NS;

class SignPresenter extends BasePresenter
{
        public function renderDefault()
        {
                $this->getHttpResponse()->setHeader('X-Frame-Options', "SAMEORIGIN");
        }

	/**
	 * Sign in form component factory.
	 * @return Nette\Application\UI\Form
	 */
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
			$this->getUser()->login($values->username, $values->password);

			if ($values->remember)
				$this->getUser()->setExpiration('+ 14 days', FALSE);
			else
				$this->getUser()->setExpiration('+ 20 minutes', TRUE);

			$this->redirect('Homepage:');

		} catch (NS\AuthenticationException $e) {
			$form->addError($e->getMessage());
		}
	}
}
