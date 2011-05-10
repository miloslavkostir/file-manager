<?php

use Nette\Environment;
use Nette\Application\UI\Form;

class FMViewSelector extends FileManager
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
        
        $template->setFile(__DIR__ . '/FMViewSelector.latte');

        // set language
        $lang_file = __DIR__ . '/../../locale/FileManager.'. $this->config['lang'].'.mo';
        if (file_exists($lang_file))
            $template->setTranslator(new GettextTranslator($lang_file));
        else
             throw new Exception ("Language file " . $lang_file . " doesn't exist! Application can not be loaded!");

        $template->actualdir = $actualdir;
        $template->changeViewForm = $this['changeViewForm'];

        $this['changeViewForm']->setDefaults(array(
                    'view' => $namespace->view
        ));

        $template->render();
    }

    public function createComponentChangeViewForm()
    {
        $translator = new GettextTranslator(__DIR__ . '/../../locale/FileManager.' . $this->config['lang'] . '.mo');

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