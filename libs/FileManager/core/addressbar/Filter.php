<?php

use Nette\Application\UI\Form;

class Filter extends FileManager
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
        $template->setFile(__DIR__ . '/Filter.latte');
        $template->setTranslator($this['system']->getTranslator());

        $this['filterForm']->setDefaults(array(
                    'phrase' => $session->mask
        ));

        $template->render();
    }

    public function createComponentFilterForm()
    {
        $translator = $this['system']->getTranslator();
        $form = new Form;
        $form->setTranslator($translator);
        $form->getElementPrototype()->class('fm-ajax');
        $form->addText('phrase')->getControlPrototype()->setTitle('Filter');
        $form->onSuccess[] = array($this, 'FilterFormSubmitted');

        return $form;
    }

    public function FilterFormSubmitted($form)
    {
        $session = $this->presenter->context->session->getSection('file-manager');
        $actualdir = $this['system']->getActualDir();

        $val = $form->values;
        $session->mask = $val['phrase'];
        parent::getParent()->handleShowContent($actualdir);
    }
}