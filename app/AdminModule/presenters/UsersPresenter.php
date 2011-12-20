<?php

namespace AdminModule;

use Nette\Application\UI\Form,
	Nette\Application as NA;

class UsersPresenter extends BasePresenter
{
	protected function startup()
	{
		parent::startup();

		if (!$this->user->isLoggedIn())
			$this->redirect("Sign:");

                $module = preg_replace("#:?[a-zA-Z_0-9]+$#", "", $this->getName());
                if (!$this->user->isAllowed($module))
                    throw new NA\ForbiddenRequestException();
	}

        public function handleDelete($id)
        {
                $model = $this->models->UserModel;
                $user = $model->getUser($id);

                if ($user || $this->user->id <> $id) {
                    $model->deleteUser($id);
                    $this->flashMessage("User has been deleted.");
                } else
                    $this->flashMessage("Record not found", "warning");

                if ($this->isAjax())
                        $this->invalidateControl("users");
                else
                        $this->redirect("this");
        }

        public function handleAdd()
        {
                $this->template->action = "add";
                if ($this->isAjax())
                        $this->invalidateControl("action");
                else
                        $this->redirect("this");
        }

        public function handleEdit($id)
        {
                $row = $this->models->UserModel->getUser($id);

                if ($row || $this->user->id <> $id) {
                    $this->template->action = "edit";
                    $this["editUserForm"]->setDefaults($row[0]);
                }
                    $this->flashMessage("Record not found", "warning");

                if ($this->isAjax())
                    $this->invalidateControl("action");
                else
                    $this->redirect("this");
        }

        public function renderDefault()
        {
                $datasource = $this->models->UserModel->getUsers()
                                    ->where("id <> %i", $this->user->id)
                                    ->toDataSource();
                $this["paginator"]->paginator->itemCount = $datasource->count();
                $this->template->users = $datasource->applyLimit($this["paginator"]->paginator->itemsPerPage, $this["paginator"]->paginator->offset)->fetchAll();
        }

        protected function createComponentAddUserForm()
        {
                $roles = $this->context->authorizator->roles;
                $roots = $this->models->SettingsModel->getRoots()->fetchPairs();

                $form = new Form;
                $form->addText("username", "Username")
                        ->setRequired("Please set item '%label'");
                $form->addPassword("password", "Password");
                $form->addPassword("password2", "Confirm password")
                        ->addRule(Form::EQUAL, "Passwords are not the same", $form["password"]);
                $form->addText("real_name", "Real name")
                        ->setRequired("Please set item '%label'");
                $form->addSelect("uploadroot", "Upload root", $roots);
                $form->addText("uploadpath", "Upload path");
                $form->addText("lang", "Language");
                $form->addText("quota_limit", "Quota limit");
                $form->addSelect("role", "Role:", $roles)
                        ->setRequired("Please enter name.");
                $form->addCheckbox("readonly", "Read-only");
                $form->addCheckbox("cache", "Enable cache");
                $form->addCheckbox("quota", "Quota");
                $form->addCheckbox("has_share", "Sharing");
                $form->addSubmit("save", "Save")
                        ->setAttribute("class", "ui-button ui-button-text-only ui-widget ui-state-default ui-corner-all");
                $form->addProtection("Please submit this form again (security token has expired).");

                $form->onSuccess[] = callback($this, "addUserFormSubmitted");

                $form["uploadroot"]->addConditionOn($form["has_share"], Form::EQUAL, TRUE)
                        ->addRule(Form::FILLED, "Please set item '%label'");
                $form["uploadpath"]->addConditionOn($form["has_share"], Form::EQUAL, TRUE)
                        ->addRule(Form::FILLED, "Please set item '%label'");

                return $form;
        }

        protected function createComponentEditUserForm()
        {
                $roles = $this->context->authorizator->roles;
                $roots = $this->models->SettingsModel->getRoots()->fetchPairs();

                $form = new Form;
                $form->addText("username", "Username:")
                        ->setRequired("Please enter username.");
                $form->addText("real_name", "Real name:")
                        ->setRequired("Please enter real name.");
                $form->addSelect("uploadroot", "Upload root:", $roots);
                $form->addText("uploadpath", "Upload path:");
                $form->addText("lang", "Language:");
                $form->addText("quota_limit", "Quota limit:");
                $form->addSelect("role", "Role:", $roles)
                        ->setRequired("Please enter name.");
                $form->addCheckbox("readonly", "Read-only");
                $form->addCheckbox("cache", "Cache");
                $form->addCheckbox("quota", "Quota");
                $form->addCheckbox("has_share", "Shares enabled");
                $form->addHidden("id")
                        ->setRequired("Unknown record.");
                $form->addSubmit("save", "Save")->setAttribute("class", "default")
                        ->setAttribute("class", "ui-button ui-button-text-only ui-widget ui-state-default ui-corner-all");
                $form->addProtection("Please submit this form again (security token has expired).");

                $form->onSuccess[] = callback($this, "editUserFormSubmitted");

                $form["uploadroot"]->addConditionOn($form["has_share"], Form::EQUAL, TRUE)
                        ->addRule(Form::FILLED, "Please set item '%label'");
                $form["uploadpath"]->addConditionOn($form["has_share"], Form::EQUAL, TRUE)
                        ->addRule(Form::FILLED, "Please set item '%label'");

                return $form;
        }

        public function addUserFormSubmitted(Form $form)
        {
                $model = $this->models->UserModel;
                $values = $form->values;
                unset($values->password2);

                if ($model->usernameExist($values["username"]))
                        $this->flashMessage("Username " . $values["username"] . " already exist.", "warning");
                else {
                        $model->addUser($values);
                        $this->flashMessage("User has been added.");
                        if ($this->isAjax())
                                $this->invalidateControl("users");
                        else
                                $this->redirect("this");
                }
        }

        public function editUserFormSubmitted(Form $form)
        {
                $values = $form->values;
                $model = $this->models->UserModel;

                if ($this->user->id == $values->id)
                        $this->flashMessage("Can not edit logged user.", "warning");
                else {
                        if ($model->usernameExist($values->username, $values->id))
                                $this->flashMessage("Username " . $values->username . " already exist.", "warning");
                        else {
                                $model->updateUser($values->id, $values);
                                $this->flashMessage("User has been updated.");
                                if ($this->isAjax())
                                        $this->invalidateControl("users");
                                else
                                        $this->redirect("this");
                        }
                }
        }

        public function createComponentPaginator()
        {
                $vp = new \VisualPaginator;
                $vp->paginator->itemsPerPage = 10;
                return $vp;
        }
}