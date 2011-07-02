<?php

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
        $session = $this->presenter->context->session->getSection('file-manager');

        $template = $this->template;
        $template->setFile(__DIR__ . '/ViewSelector.latte');
        $template->setTranslator(parent::getParent()->getTranslator());

        $this['changeViewForm']->setDefaults(array(
                    'view' => $session->view
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

        $form->onSuccess[] = array($this, 'ChangeViewFormSubmitted');

        return $form;
    }

    public function ChangeViewFormSubmitted($form)
    {
        $val = $form->values;
        $session = $this->presenter->context->session->getSection('file-manager');
        $session->view = $val['view'];
        parent::getParent()->handleShowContent($this['system']->getActualDir());
    }
}