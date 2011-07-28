<?php

use Nette\Application\UI\Form,
	Nette\Application as NA,
        Nette\Application\UI\Presenter;

class UsersControl extends \Nette\Application\UI\Control
{
    /** @var Model */
    private $model;

    /** @var Identity */
    private $user;

    public function __construct()
    {
            parent::__construct();
            $this->model = new \UserModel;
            $this->monitor('Presenter');
    }

    protected function attached($presenter)
    {
            if ($presenter instanceof Presenter) {
                $this->user = $this->presenter->user;
                $this->invalidateControl('users');
            }
            parent::attached($presenter);
    }

    public function handleDelete($id)
    {
            $user = $this->model->getUser($id);

            if (empty($user) || $this->user->id == $id)
                    throw new NA\BadRequestException('Record not found');

            $this->model->deleteUser($id);
            $this->presenter->flashMessage('User has been deleted.');
            if ($this->presenter->isAjax())
                    $this->invalidateControl('users');
            else
                    $this->presenter->redirect('this');
    }

    public function handleAdd()
    {
            $this->template->action = 'add';
            if ($this->presenter->isAjax())
                    $this->invalidateControl('action');
            else
                    $this->presenter->redirect('this');
    }

    public function handleEdit($id)
    {
            $this->template->action = 'edit';
            $row = $this->model->getUser($id);

            if (!$row[0] || $this->user->id == $id)
                    throw new NA\BadRequestException('Record not found');

            $this['editUserForm']->setDefaults($row[0]);

            if ($this->presenter->isAjax())
                    $this->invalidateControl('action');
            else
                    $this->presenter->redirect('this');
    }

    public function render()
    {
            $template = $this->template;
            $template->setFile(__DIR__ . '/UsersControl.latte');
            $datasource = $this->model->getUsers()
                                ->where('id <> %i', $this->user->id)
                                ->toDataSource();
            $this['paginator']->paginator->itemCount = $datasource->count();
            $template->users = $datasource->applyLimit($this['paginator']->paginator->itemsPerPage, $this['paginator']->paginator->offset)->fetchAll();
            $template->render();
    }

    protected function createComponentAddUserForm()
    {
            $roles = $this->model->getRoles()->fetchPairs();

            $model = new \SettingsModel;
            $roots = $model->getRoots()->fetchPairs();

            $form = new Form;
            $form->addText('username', 'Username')
                    ->setRequired("Please set item '%label'");
            $form->addPassword('password', 'Password');
            $form->addText('real_name', 'Real name')
                    ->setRequired("Please set item '%label'");
            $form->addSelect('uploadroot', 'Upload root', $roots);
            $form->addText('uploadpath', 'Upload path');
            $form->addText('lang', 'Language');
            $form->addText('quota_limit', 'Quota limit');
            $form->addSelect('role', 'Role:', $roles)
                    ->setRequired('Please enter name.');
            $form->addCheckbox('readonly', 'Read-only');
            $form->addCheckbox('cache', 'Enable cache');
            $form->addCheckbox('quota', 'Quota');
            $form->addCheckbox('imagemagick', 'Imagemagick');
            $form->addCheckbox('has_share', 'Sharing');
            $form->addSubmit('save', 'Save')
                    ->setAttribute('class', 'ui-button ui-button-text-only ui-widget ui-state-default ui-corner-all');
            $form->addProtection('Please submit this form again (security token has expired).');

            $form->onSuccess[] = callback($this, 'addUserFormSubmitted');

            $form['uploadroot']->addConditionOn($form['has_share'], Form::EQUAL, TRUE)
                    ->addRule(Form::FILLED, "Please set item '%label'");
            $form['uploadpath']->addConditionOn($form['has_share'], Form::EQUAL, TRUE)
                    ->addRule(Form::FILLED, "Please set item '%label'");

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
            $form->addSubmit('save', 'Save')->setAttribute('class', 'default')
                    ->setAttribute('class', 'ui-button ui-button-text-only ui-widget ui-state-default ui-corner-all');
            $form->addProtection('Please submit this form again (security token has expired).');

            $form->onSuccess[] = callback($this, 'editUserFormSubmitted');

            $form['uploadroot']->addConditionOn($form['has_share'], Form::EQUAL, TRUE)
                    ->addRule(Form::FILLED, "Please set item '%label'");
            $form['uploadpath']->addConditionOn($form['has_share'], Form::EQUAL, TRUE)
                    ->addRule(Form::FILLED, "Please set item '%label'");

            return $form;
    }

    public function addUserFormSubmitted(Form $form)
    {
            $values = $form->values;

            if ($this->model->usernameExist($values['username']))
                    $this->presenter->flashMessage('Username ' . $values['username'] . ' already exist.', 'warning');
            else {
                    $this->model->addUser($values);
                    $this->presenter->flashMessage('User has been added.');
                    if ($this->presenter->isAjax())
                            $this->invalidateControl('users');
                    else
                            $this->presenter->redirect('this');
            }
    }

    public function editUserFormSubmitted(Form $form)
    {
            $values = $form->values;
            $id = $values['id'];

            if ($this->user->id == $id)
                    throw new NA\BadRequestException('Can not edit logged user.');

            if ($this->model->usernameExist($values['username']))
                    $this->presenter->flashMessage('Username ' . $values['username'] . ' already exist.', 'warning');
            else {
                    $this->model->updateUser($id, $values);
                    $this->presenter->flashMessage('User has been updated.');
                    if ($this->presenter->isAjax())
                            $this->invalidateControl('users');
                    else
                            $this->presenter->redirect('this');
            }
    }

    function checkUsername($username)
    {
            
    }

    public function createComponentPaginator()
    {
            $vp = new \VisualPaginator;
            $vp->paginator->itemsPerPage = 10;
            return $vp;
    }
}