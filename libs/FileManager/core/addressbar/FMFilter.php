<?php

use Nette\Application\UI\Form;

class FMFilter extends FileManager
{
    /** @var array */
    public $config;

    /** @var string */
    public $actualdir;

    public function __construct()
    {
        parent::__construct();
    }

    public function render()
    {
        $actualdir = $this->actualdir;
        $template = $this->template;
        $template->setFile(__DIR__ . '/FMFilter.latte');

        // set language
        $lang_file = __DIR__ . '/../../locale/FileManager.'. $this->config['lang'].'.mo';
        if (file_exists($lang_file))
            $template->setTranslator(new GettextTranslator($lang_file));
        else
             throw new Exception ("Language file " . $lang_file . " doesn't exist! Application can not be loaded!");

        $template->filterForm = $this['filterForm'];

        // set dir for filtering
        if (empty($actualdir))
                $this['filterForm']->setDefaults(array(
                            'actualdir' => parent::getParent()->getRootname()
                ));
        else
                $this['filterForm']->setDefaults(array(
                            'actualdir' => $actualdir
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
        $form->addHidden('actualdir');
        $form->onSubmit[] = array($this, 'FilterFormSubmitted');

        return $form;
    }

    public function FilterFormSubmitted($form)
    {
        $val = $form->values;
        $this['content']->mask = $val['phrase'];
        parent::getParent()->handleShowContent($val['actualdir']);
    }
}