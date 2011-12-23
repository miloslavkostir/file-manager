<?php

namespace AdminModule;

use Nette\Application\UI\Form,
        Nette\Application as NA;

class ProfilePresenter extends BasePresenter
{
	protected function startup()
	{
		parent::startup();

                $user = $this->user;

		if (!$user->isLoggedIn())
			$this->redirect("Sign:");

                $module = preg_replace("#:?[a-zA-Z_0-9]+$#", "", $this->getName());
                if (!$user->isAllowed($module))
                    throw new NA\ForbiddenRequestException();
	}

        public function beforeRender()
        {
                if ($this->hasFlashSession())
                    $this->invalidateControl("flashMessages");
        }

        public function renderDefault()
        {
                $profile = $this->models->UserModel->getUser($this->user->id);
                if (!$profile)
                        throw new NA\BadRequestException("Record not found");
                $this["profileForm"]->setDefaults($profile);
        }

        protected function createComponentChangePassForm()
        {
                $form = new Form;
                $form->addPassword("password1", "New password");
                $form->addPassword("password2", "Confirm password")                        
                        ->addRule(Form::EQUAL, "Passwords are not the same", $form["password1"]);
                $form->addCheckBox("logout", "Logout after password change");
                $form->addSubmit("save", "Save")
                        ->setAttribute("class", "ui-button ui-button-text-only ui-widget ui-state-default ui-corner-all");
                $form->addProtection("Please submit this form again (security token has expired).");

                $form->onSuccess[] = callback($this, "changePassFormSubmitted");

                return $form;
        }

        public function changePassFormSubmitted(Form $form)
        {
                $values = $form->values;
                $this->models->UserModel->changePassword($this->user->id, $values["password2"]);
                if ($values["logout"])
                    $this->redirect("Sign:out");
                else {
                    $this->flashMessage("Password was changed", "info");
                    $this->redirect("this");
                }
        }

        protected function createComponentProfileForm()
        {
                $roles = $this->context->authorizator->roles;
                $roots = $this->models->SettingsModel->getRoots()->fetchPairs();

                if (!$this->user->isAllowed("Root"))
                    unset($roles["root"]);

                $form = new Form;
                $form->addText("username", "Username:")
                        ->setRequired("Please set item '%label'");
                $form->addText("real_name", "Real name:")
                        ->setRequired("Please set item '%label'");
                $form->addSelect("uploadroot", "Upload root:", $roots);
                $form->addText("uploadpath", "Upload path:");
                $form->addText("lang", "Language:");
                $form->addText("quota_limit", "Quota limit:");
                $form->addSelect("role", "Role:", $roles)
                        ->setRequired("Please set item '%label'");
                $form->addCheckbox("readonly", "Read-only");
                $form->addCheckbox("cache", "Cache");
                $form->addCheckbox("quota", "Quota");
                $form->addCheckbox("has_share", "Shares enabled");
                $form->addProtection("Please submit this form again (security token has expired).");

                $form["uploadroot"]->addConditionOn($form["has_share"], Form::EQUAL, TRUE)
                        ->addRule(Form::FILLED, "Please set item '%label'");
                $form["uploadpath"]->addConditionOn($form["has_share"], Form::EQUAL, TRUE)
                        ->addRule(Form::FILLED, "Please set item '%label'");

                $form->addSubmit("save", "Save")
                        ->setAttribute("class", "ui-button ui-button-text-only ui-widget ui-state-default ui-corner-all")
                        ->onClick[] = callback($this, "profileFormSubmitted");
                $form->addSubmit("cancel", "Cancel")
                        ->setAttribute("class", "ui-button ui-button-text-only ui-widget ui-state-default ui-corner-all")
                        ->onClick[] = callback($this, "profileFormSubmitted");
                $form->addSubmit("delete", "Delete profile")
                        ->setAttribute("class", "ui-button ui-button-text-only ui-widget ui-state-default ui-corner-all")
                        ->onClick[] = callback($this, "profileFormSubmitted");

                return $form;
        }

        public function profileFormSubmitted(\Nette\Forms\Controls\SubmitButton $button)
        {
                $model = $this->models->UserModel;
                $form = $button->form;
                $values = $form->values;

                if ($values->role == "root" && !$this->user->isAllowed("Root"))
                    throw new NA\ForbiddenRequestException();

                if ($form["save"]->submittedBy) {
                        if ($model->usernameExist($values["username"], $this->user->id))
                                $this->flashMessage("Username " . $values["username"] . " already exist.", "warning");
                        else {
                                $model->updateUser($this->user->id, $values);
                                $this->flashMessage("Your profile has been updated.");
                                $this->redirect("this");
                        }
                } elseif ($form["cancel"]->submittedBy) {
                        $this->redirect("this");
                } elseif ($form["delete"]->submittedBy) {
                        if (count($model->getUsers()) < 2) {
                                $this->flashMessage("Can not delete last profile", "warning");
                                $this->redirect("this");
                        } else {
                                $model->deleteUser($this->user->id);
                                $this->user->logOut();
                                $this->redirect("Sign:");
                        }
                }
        }
}