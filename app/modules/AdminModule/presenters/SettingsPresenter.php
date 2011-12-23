<?php

namespace AdminModule;

use Nette\Application\UI\Form,
        Nette\Application as NA;

class SettingsPresenter extends BasePresenter
{
	protected function startup()
	{
		parent::startup();

                $user = $this->user;
                if (!$user->isLoggedIn()) {

			if ($user->logoutReason === \Nette\Http\User::INACTIVITY)
				$this->flashMessage("You have been signed out due to inactivity. Please sign in again.");
			$backlink = $this->application->storeRequest();
                        $this->redirect("Sign:", array("backlink" => $backlink));
                }

                $module = preg_replace("#:?[a-zA-Z_0-9]+$#", "", $this->getName());
                if (!$user->isAllowed($module))
                    throw new NA\ForbiddenRequestException();
	}

        public function handleDelete($file)
        {
                if (!$this->user->isAllowed("Root"))
                    throw new NA\ForbiddenRequestException();

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
                if (!$this->user->isAllowed("Root"))
                    throw new NA\ForbiddenRequestException();

                $this->models->BackupModel->save();
                $this->flashMessage("Backup was finished seccessfuly.", "info");
                if ($this->isAjax())
                    $this->invalidateControl("backup");
                else
                    $this->redirect("this");
        }

        public function handleRestore($file)
        {
                if (!$this->user->isAllowed("Root"))
                    throw new NA\ForbiddenRequestException();

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
                if (!$this->user->isAllowed("Root"))
                    throw new NA\ForbiddenRequestException();

                $path = $this->models->BackupModel->getFile($file);

                if (file_exists($path))
                    $this->sendResponse(new NA\Responses\FileResponse($path, NULL, NULL));
                else {
                    $this->flashMessage("File '$file' does not exist.", "warning");
                    $this->redirect("this");
                }
        }

        public function beforeRender()
        {
                if ($this->hasFlashSession())
                    $this->invalidateControl("flashMessages");
        }

        public function renderBackup()
        {
                if (!$this->user->isAllowed("Root"))
                    throw new NA\ForbiddenRequestException();

                $this->template->items = $this->models->BackupModel->load();
        }

        public function renderConfiguration()
        {
                if (!$this->user->isAllowed("Root"))
                    throw new NA\ForbiddenRequestException();

                $this["configurationForm"]["security"]->setDefaults($this->models->ConfigurationModel->load());
        }

        protected function createComponentRoots()
        {
                $root = new \RootControl;
                return $root;
        }

        protected function createComponentConfigurationForm()
        {
                $form = new Form;
                $sub1 = $form->addContainer("security");
                $sub1->addText("salt", "Salt hash");
                $sub1->addText("salt2", "Confirm salt hash")
                        ->addRule(Form::EQUAL, "Salt hashes are not the same", $sub1["salt"]);
                $form->addSubmit("save", "Save")
                        ->setAttribute("class", "ui-button ui-button-text-only ui-widget ui-state-default ui-corner-all");
                $form->addProtection("Please submit this form again (security token has expired).");

                $form->onSuccess[] = callback($this, "configurationFormSubmitted");

                return $form;
        }

        public function configurationFormSubmitted(Form $form)
        {
                if (!$this->user->isAllowed("Root"))
                    throw new NA\ForbiddenRequestException();

                $this->models->ConfigurationModel->save($form->values->security->salt);
                $this->flashMessage("Configuration was changed", "info");
                $this->redirect("this");
        }
}