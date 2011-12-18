<?php

namespace AdminModule;

use Nette\Application\UI\Form;

class SettingsPresenter extends BasePresenter
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

        public function handleDelete($file)
        {
                if ($this->models->BackupModel->delete($file))
                    $this->flashMessage("Backup was deleted seccessfuly.", "info");
                else {
                    $this->flashMessage("An error occured during backup deleting.", "error");
                    \Nette\Diagnostics\Debugger::log("Can not delete backup '$file'.", "error");
                }

                if ($this->isAjax())
                    $this->invalidateControl("backup");
                else
                    $this->redirect("this");
        }

        public function handleBackup()
        {
                $this->models->BackupModel->save();
                $this->flashMessage("Backup was finished seccessfuly.", "info");
                if ($this->isAjax())
                    $this->invalidateControl("backup");
                else
                    $this->redirect("this");
        }

        public function handleRestore($file)
        {
                if ($this->models->BackupModel->restore($file))
                    $this->flashMessage("Backup was restored successfuly.", "info");
                else
                    $this->flashMessage("An error occured during backup restore.", "error");

                if ($this->isAjax())
                    $this->invalidateControl("backup");
                else
                    $this->redirect("this");
        }

        public function handleDownload($file)
        {
                $path = $this->models->BackupModel->getFile($file);

                if (file_exists($path))
                    $this->sendResponse(new \Nette\Application\Responses\FileResponse($path, NULL, NULL));
                else {
                    $this->flashMessage("File '$file' does not exist.", "warning");
                    $this->redirect("this");
                }
        }

        public function renderBackup()
        {
                $this->template->items = $this->models->BackupModel->load();
        }

        protected function createComponentRoots()
        {
                $root = new \RootControl;
                return $root;
        }

	protected function createComponentProfile()
	{
		$profile = new \ProfileControl;
		return $profile;
	}

        protected function createComponentChangePassForm()
        {
                $form = new Form;
                $form->addPassword('password1', 'New password');
                $form->addPassword('password2', 'Confirm password')                        
                        ->addRule(Form::EQUAL, "Passwords are not the same", $form["password1"]);
                $form->addCheckBox('logout', "Logout after password change");
                $form->addSubmit('save', 'Save')
                        ->setAttribute('class', 'ui-button ui-button-text-only ui-widget ui-state-default ui-corner-all');
                $form->addProtection('Please submit this form again (security token has expired).');

                $form->onSuccess[] = callback($this, 'changePassFormSubmitted');

                return $form;
        }

        public function changePassFormSubmitted(Form $form)
        {
                $values = $form->values;
                $this->models->UserModel->changePassword($this->user->id, $values['password2']);
                if ($values["logout"])
                    $this->redirect("Sign:out");
                else {
                    $this->flashMessage("Password was changed", "info");
                    $this->redirect("this");
                }
        }
}