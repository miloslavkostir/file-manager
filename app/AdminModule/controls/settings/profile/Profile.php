<?php

use Nette\Application\UI\Form,
	Nette\Application as NA,
        Nette\Application\UI\Presenter;

class ProfileControl extends \Nette\Application\UI\Control
{
    /** @var Model */
    private $users,
            $settings;

    /** @var Identity */
    private $profile;

    public function __construct()
    {
            parent::__construct();
            $this->monitor('Presenter');
    }

    protected function attached($presenter)
    {
            if ($presenter instanceof Presenter) {
                    $this->profile = $this->presenter->user;
                    $this->users = $this->presenter->models->UserModel;
                    $this->settings = $this->presenter->models->SettingsModel;
            }
            parent::attached($presenter);
    }

    public function render()
    {
            $template = $this->template;
            $template->setFile(__DIR__ . '/Profile.latte');
            $profile = $this->users->getUser($this->profile->id);
            if (!$profile[0])
                    throw new NA\BadRequestException('Record not found');
            $this['profileForm']->setDefaults($profile[0]);
            $template->render();
    }

    protected function createComponentProfileForm()
    {
            $roles = $this->presenter->context->authorizator->roles;
            $roots = $this->settings->getRoots()->fetchPairs();

            $form = new Form;
            $form->addText('username', 'Username:')
                    ->setRequired('Please enter username.');
            $form->addText('real_name', 'Real name:')
                    ->setRequired('Please enter real name.');
            $form->addSelect('uploadroot', 'Upload root:', $roots);
            $form->addText('uploadpath', 'Upload path:');
            $form->addText('lang', 'Language:');
            $form->addText('quota_limit', 'Quota limit:');
            $form->addSelect('role', 'Role:', $roles)
                    ->setRequired('Please enter name.');
            $form->addCheckbox('readonly', 'Read-only');
            $form->addCheckbox('cache', 'Cache');
            $form->addCheckbox('quota', 'Quota');
            $form->addCheckbox('has_share', 'Shares enabled');
            $form->addProtection('Please submit this form again (security token has expired).');

            $form['uploadroot']->addConditionOn($form['has_share'], Form::EQUAL, TRUE)
                    ->addRule(Form::FILLED, "Please set item '%label'");
            $form['uploadpath']->addConditionOn($form['has_share'], Form::EQUAL, TRUE)
                    ->addRule(Form::FILLED, "Please set item '%label'");

            $form->addSubmit('save', 'Save')
                    ->setAttribute('class', 'ui-button ui-button-text-only ui-widget ui-state-default ui-corner-all')
                    ->onClick[] = callback($this, 'profileFormSubmitted');
            $form->addSubmit('cancel', 'Cancel')
                    ->setAttribute('class', 'ui-button ui-button-text-only ui-widget ui-state-default ui-corner-all')
                    ->onClick[] = callback($this, 'profileFormSubmitted');
            $form->addSubmit('delete', 'Delete profile')
                    ->setAttribute('class', 'ui-button ui-button-text-only ui-widget ui-state-default ui-corner-all')
                    ->onClick[] = callback($this, 'profileFormSubmitted');

            return $form;
    }

    public function profileFormSubmitted(Nette\Forms\Controls\SubmitButton $button)
    {
            $form = $button->form;
            $values = $form->values;

            if ($form['save']->submittedBy) {
                    if ($this->users->usernameExist($values['username'], $this->profile->id))
                            $this->presenter->flashMessage('Username ' . $values['username'] . ' already exist.', 'warning');
                    else {
                            $this->users->updateUser($this->profile->id, $values);
                            $this->presenter->flashMessage('Your profile has been updated.');
                            $this->presenter->redirect('Settings:');
                    }
            } elseif ($form['cancel']->submittedBy) {
                    $this->presenter->redirect('Settings:');
            } elseif ($form['delete']->submittedBy) {
                    if (count($this->users->getUsers()) < 2) {
                            $this->presenter->flashMessage('Can not delete last profile', 'warning');
                            $this->presenter->redirect('this');
                    } else {
                            $this->users->deleteUser($this->profile->id);
                            $this->presenter->user->logOut();
                            $this->presenter->redirect('Sign:');
                    }
            }
    }
}