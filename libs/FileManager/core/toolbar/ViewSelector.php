<?php

use Nette\Environment;
use Nette\Application\UI\Form;

class ViewSelector extends FileManager
{
    /** @var array */
    public $config;

    public function __construct()
    {
        parent::__construct();
    }

    public function render()
    {
        $namespace = Environment::getSession('file-manager');

        $template = $this->template;
        $template->setFile(__DIR__ . '/ViewSelector.latte');
        $template->setTranslator(parent::getParent()->getTranslator());
        $template->changeViewForm = $this['changeViewForm'];

        $this['changeViewForm']->setDefaults(array(
                    'view' => $namespace->view
        ));

        $template->render();
    }

    public function createComponentChangeViewForm()
    {
        $translator = parent::getParent()->getTranslator();

        $items = array(
            'large' => $translator->translate('Large images'),
            'small' => $translator->translate('Small images'),
            'list' => $translator->translate('List'),
            'details' => $translator->translate('Details')
        );

        $form = new Form;
        $form->getElementPrototype()->class('fm-ajax');
        $form->addSelect('view', NULL, $items);

        $form->onSubmit[] = array($this, 'ChangeViewFormSubmitted');

        return $form;
    }

    public function ChangeViewFormSubmitted($form)
    {
        $val = $form->values;
        $namespace = Environment::getSession('file-manager');        
        $namespace->view = $val['view'];
        parent::getParent()->handleShowContent($namespace->actualdir);
    }
}