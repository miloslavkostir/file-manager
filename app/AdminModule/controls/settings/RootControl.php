<?php

use Nette\Application\UI\Form,
	Nette\Application as NA;

class RootControl extends \Nette\Application\UI\Control
{
    /** @var Model */
    private $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new \SettingsModel;
    }

    public function handleDelete($id)
    {
        $root = $this->model->getRoot($id);

        if (empty($root))
                throw new NA\BadRequestException('Record not found');
        else {
                $this->model->deleteRoot($id);
                $this->presenter->flashMessage('Root has been deleted.');
        }

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
        $row = $this->model->getRoot($id);

        if (!$row[0]) {
                throw new NA\BadRequestException('Record not found');
        }
        $this['editRootForm']->setDefaults($row[0]);

        if ($this->presenter->isAjax())
                $this->invalidateControl('action');
        else
                $this->presenter->redirect('this');
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/RootControl.latte');
        $template->roots = $this->model->getRoots();
        $template->render();
    }

    protected function createComponentAddRootForm()
    {
            $form = new Form;
            $form->addText('path', 'Path:')
                    ->setRequired('Please enter the path.');
            $form->addSubmit('save', 'Save')
                    ->setAttribute('class', 'ui-button ui-button-text-only ui-widget ui-state-default ui-corner-all');
            $form->addProtection('Please submit this form again (security token has expired).');
            $form->onSuccess[] = callback($this, 'addRootFormSubmitted');
            return $form;
    }

    protected function createComponentEditRootForm()
    {
            $form = new Form;
            $form->addText('path')
                    ->setRequired('Please enter the path.');
            $form->addHidden('id')
                    ->setRequired('Unknown record.');
            $form->addSubmit('save', 'Save')->setAttribute('class', 'default')
                    ->setAttribute('class', 'ui-button ui-button-text-only ui-widget ui-state-default ui-corner-all');
            $form->addProtection('Please submit this form again (security token has expired).');
            $form->onSuccess[] = callback($this, 'editRootFormSubmitted');
            return $form;
    }

    public function addRootFormSubmitted(Form $form)
    {
        $this->model->addRoot($form->values);
        $this->presenter->flashMessage('Root has been added.');
        $this->presenter->redirect('this');
    }

    public function editRootFormSubmitted(Form $form)
    {
        $this->model->updateRoot($form->values['id'], $form->values);
        $this->presenter->flashMessage('Root has been updated.');
        $this->presenter->redirect('this');
    }

    protected function createComponentFolderSelector()
    {
        $fs = new \PathSelector($_SERVER['DOCUMENT_ROOT'], true);
        return $fs;
    }    
}