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
        $namespace = $this->presenter->context->session->getNamespace('file-manager');

        $template = $this->template;
        $template->setFile(__DIR__ . '/Filter.latte');
        $template->setTranslator(parent::getParent()->getTranslator());

        $this['filterForm']->setDefaults(array(
                    'phrase' => $namespace->mask
        ));
        
        $template->render();
    }

    public function createComponentFilterForm()
    {
        $translator = parent::getParent()->getTranslator();
        $form = new Form;
        $form->setTranslator($translator);
        $form->getElementPrototype()->class('fm-ajax');
        $form->addText('phrase')->getControlPrototype()->setTitle('Filter');
        $form->onSubmit[] = array($this, 'FilterFormSubmitted');

        return $form;
    }

    public function FilterFormSubmitted($form)
    {
        $namespace = $this->presenter->context->session->getNamespace('file-manager');
        $actualdir = $this['system']->getActualDir();

        $val = $form->values;
        $namespace->mask = $val['phrase'];
        parent::getParent()->handleShowContent($actualdir);
    }
}