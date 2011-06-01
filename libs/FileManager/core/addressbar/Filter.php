<?php

use Nette\Application\UI\Form;
use Nette\Environment;

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
        $namespace = Environment::getSession('file-manager');
        $actualdir = $namespace->actualdir;

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
        $namespace = Environment::getSession('file-manager');
        $actualdir = $namespace->actualdir;
        
        $val = $form->values;
        $namespace->mask = $val['phrase'];
        parent::getParent()->handleShowContent($actualdir);
    }
}