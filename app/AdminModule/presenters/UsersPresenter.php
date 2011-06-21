<?php

namespace AdminModule;

use Nette\Application\UI\Form,
	Nette\Application as NA;

class UsersPresenter extends BasePresenter
{
	private $model;

	protected function startup()
	{
		parent::startup();

		$this->model = new \UserModel;

		// user authentication
		if (!$this->user->isLoggedIn()) {
			if ($this->user->logoutReason === \Nette\Http\User::INACTIVITY) {
				$this->flashMessage('You have been signed out due to inactivity. Please sign in again.');
			}
			$backlink = $this->application->storeRequest();
			$this->redirect('Sign:', array('backlink' => $backlink));
		}
	}

	public function renderDefault()
	{
		$this->template->users = $this->model->getUsers()->orderBy('username');
	}

	public function renderAdd()
	{
		$this['userForm']['save']->caption = 'Add';
	}

	public function renderEdit($id = 0)
	{
		$form = $this['userForm'];
		if (!$form->isSubmitted()) {
			$row = $this->model->getUser($id);
			if (!$row[0]) {
				throw new NA\BadRequestException('Record not found');
			}
			$form->setDefaults($row[0]);
		}
	}

	public function renderDelete($id = 0)
	{
                $user = $this->model->getUser($id);
		$this->template->currentUser = $user[0];
		if (!$this->template->currentUser) {
			throw new NA\BadRequestException('Record not found');
		}
	}

	protected function createComponentUserForm()
	{
                $roles = $this->model->getRoles()->fetchPairs();

                $rootModel = new \SettingsModel;
                $roots = $rootModel->getRoots()->fetchPairs();

		$form = new Form;
		$form->addText('username', 'Username:')
			->setRequired('Please enter username.');
		$form->addText('password', 'Password:');
		$form->addText('real_name', 'Real name:')
			->setRequired('Please enter real name.');
		$form->addSelect('uploadroot', 'Upload root:', $roots)
			->setRequired('Please select upload root.');
		$form->addText('uploadpath', 'Upload path:')
			->setRequired('Please enter upload path.');
		$form->addText('lang', 'Language:');
		$form->addText('quota_limit', 'Quota limit:');
		$form->addSelect('role', 'Role:', $roles)
			->setRequired('Please enter name.');
		$form->addCheckbox('readonly', 'Read-only');
		$form->addCheckbox('cache', 'Cache');
		$form->addCheckbox('quota', 'Quota');
		$form->addCheckbox('imagemagick', 'Imagemagick');

		$form->addSubmit('save', 'Save')->setAttribute('class', 'default');
		$form->addSubmit('cancel', 'Cancel')->setValidationScope(NULL);
		$form->onSubmit[] = callback($this, 'userFormSubmitted');

		$form->addProtection('Please submit this form again (security token has expired).');
		return $form;
	}

	public function userFormSubmitted(Form $form)
	{
		if ($form['save']->isSubmittedBy()) {
			$id = (int) $this->getParam('id');
			if ($id > 0) {
				$this->model->updateUser($id, $form->values);
				$this->flashMessage('The user has been updated.');
			} else {
				$this->model->addUser($form->values);
				$this->flashMessage('The user has been added.');
			}
		}

		$this->redirect('default');
	}

	protected function createComponentDeleteForm()
	{
		$form = new Form;
		$form->addSubmit('cancel', 'Cancel');
		$form->addSubmit('delete', 'Delete')->setAttribute('class', 'default');
		$form->onSubmit[] = callback($this, 'deleteFormSubmitted');
		$form->addProtection('Please submit this form again (security token has expired).');
		return $form;
	}

	public function deleteFormSubmitted(Form $form)
	{
		if ($form['delete']->isSubmittedBy()) {
			$this->model->deleteUser($this->getParam('id'));
			$this->flashMessage('user has been deleted.');
		}

		$this->redirect('default');
	}
}