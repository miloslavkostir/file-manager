<?php

use Nette\Application\UI\Form;
use Nette\Environment;

class Navigation extends FileManager
{
    public function __construct()
    {
        parent::__construct();
    }

    public function render()
    {
        $namespace = Environment::getSession('file-manager');
        $actualdir = $namespace->actualdir;        
        $rootname = parent::getParent()->getRootname();
        $template = $this->template;
        
        $template->setFile(__DIR__ . '/Navigation.latte');

        if (empty($actualdir)) {
            $template->items = $this->getNav($rootname);
        } else
            $template->items = $this->getNav($actualdir);

        $template->locationForm = $this['locationForm'];

        $this['locationForm']->setDefaults(array(
                    'actualdir' => $actualdir,
                    'location' => $actualdir
        ));

        $template->render();
    }

    public function createComponentLocationForm()
    {
        $translator = new GettextTranslator(__DIR__ . '/../../locale/FileManager.' . $this->config["lang"] . '.mo');
        $form = new Form;
        $form->setTranslator($translator);
        $form->getElementPrototype()->class('fm-ajax');
        $form->addText('location');
        $form->addHidden('actualdir');
        $form->onSubmit[] = array($this, 'LocationFormSubmitted');

        return $form;
    }

    public function LocationFormSubmitted($form)
    {
        $val = $form->values;
        $path = parent::getParent()->getAbsolutePath($val['location']);

        if (file_exists($path))
            parent::getParent()->handleShowContent($val['location']);
        else {
            $translator = new GettextTranslator(__DIR__ . '/../../locale/FileManager.' . $this->config["lang"] . '.mo');
            parent::getParent()->flashMessage(
                $translator->translate('Directory does not exists.'),
                'warning'
            );
            parent::getParent()->handleShowContent($val['actualdir']);
        }
    }

    public function getNav($actualdir)
    {
        $var = array();
        $rootname = parent::getParent()->getRootname();
        if ($actualdir === $rootname)
                $var[] = array(
                        "name" => $rootname,
                        "link" => parent::getParent()->link("showContent", $rootname)
                );
        else {
                $nav = explode("/", $actualdir);
                $path = "/";
                foreach ($nav as $item) {
                    if (!empty($item)) {
                        $path .= $item . "/";
                        $var[] = array(
                            "name" => $item,
                            "link" => parent::getParent()->link("showContent", $path)
                        );
                    }
                }
        }

        return $var;
    }
}