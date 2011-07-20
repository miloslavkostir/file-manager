<?php

use Nette\Application\UI\Form,
	Nette\Application as NA;

class UsersControl extends \Nette\Application\UI\Control
{
    /** @var Model */
    private $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new \UserModel;
    }

    public function handleDelete($id)
    {
        $user = $this->model->getUser($id);

        if (empty($user))
                throw new NA\BadRequestException('Record not found');
        else {
                $this->model->deleteUser($id);
                $this->presenter->flashMessage('User has been deleted.');
        }
    }

    public function handleAdd()
    {
        $this->template->action = 'add';
    }

    public function handleEdit($id)
    {
        $this->template->action = 'edit';
        $row = $this->model->getUser($id);
        if (!$row[0]) {
                throw new NA\BadRequestException('Record not found');
        }
        $this['editUserForm']->setDefaults($row[0]);
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/UsersControl.latte');
        $template->users = $this->model->getUsers();
        $template->render();
    }

    protected function createComponentAddUserForm()
    {
            $roles = $this->model->getRoles()->fetchPairs();

            $model = new \SettingsModel;
            $roots = $model->getRoots()->fetchPairs();

            $form = new Form;
            $form->addText('username', 'Username:')
                    ->setRequired('Please enter username.');
            $form->addPassword('password', 'Password:');
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
            $form->addCheckbox('imagemagick', 'Imagemagick');
            $form->addCheckbox('has_share', 'Shares enabled');

            $form->addSubmit('save', 'Save')->setAttribute('class', 'default');
            $form->onSuccess[] = callback($this, 'addUserFormSubmitted');

            $form->addProtection('Please submit this form again (security token has expired).');

            $form['uploadroot']->addConditionOn($form['has_share'], Form::EQUAL, TRUE)->addRule(Form::FILLED, "Please set item '%label'");
            $form['uploadpath']->addConditionOn($form['has_share'], Form::EQUAL, TRUE)->addRule(Form::FILLED, "Please set item '%label'");

            return $form;
    }

    protected function createComponentEditUserForm()
    {
            $roles = $this->model->getRoles()->fetchPairs();

            $model = new \SettingsModel;
            $roots = $model->getRoots()->fetchPairs();

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
            $form->addCheckbox('imagemagick', 'Imagemagick');
            $form->addCheckbox('has_share', 'Shares enabled');

            $form->addHidden('id')
                    ->setRequired('Unknown record.');

            $form->addSubmit('save', 'Save')->setAttribute('class', 'default');
            $form->onSuccess[] = callback($this, 'editUserFormSubmitted');

            $form->addProtection('Please submit this form again (security token has expired).');

            $form['uploadroot']->addConditionOn($form['has_share'], Form::EQUAL, TRUE)->addRule(Form::FILLED, "Please set item '%label'");
            $form['uploadpath']->addConditionOn($form['has_share'], Form::EQUAL, TRUE)->addRule(Form::FILLED, "Please set item '%label'");

            return $form;
    }

    public function addUserFormSubmitted(Form $form)
    {
        $this->model->addUser($form->values);
        $this->presenter->flashMessage('User has been added.');
    }

    public function editUserFormSubmitted(Form $form)
    {
        $this->model->updateUser($form->values['id'], $form->values);
        $this->presenter->flashMessage('User has been updated.');
    }
}