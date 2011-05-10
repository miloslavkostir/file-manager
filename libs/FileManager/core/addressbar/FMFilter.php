<?php

use Nette\Application\UI\Form;
use Nette\Environment;

class FMFilter extends FileManager
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
        $template->setFile(__DIR__ . '/FMFilter.latte');

        // set language
        $lang_file = __DIR__ . '/../../locale/FileManager.'. $this->config['lang'].'.mo';
        if (file_exists($lang_file))
            $template->setTranslator(new GettextTranslator($lang_file));
        else
             throw new Exception ("Language file " . $lang_file . " doesn't exist! Application can not be loaded!");

        $template->filterForm = $this['filterForm'];

        $this['filterForm']->setDefaults(array(
                    'phrase' => $namespace->mask
        ));
        
        $template->render();
    }

    public function createComponentFilterForm()
    {
        $translator = new GettextTranslator(__DIR__ . '/../../locale/FileManager.' . $this->config["lang"] . '.mo');
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