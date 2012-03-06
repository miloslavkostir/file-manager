<?php

namespace Ixtrum;

use Nette\Application\UI\Form;

class Navigation extends Ixtrum
{
    public function __construct($userConfig)
    {
        parent::__construct($userConfig);
    }

    public function handleRefreshContent()
    {
        $actualdir = $this->context->system->getActualDir();

        if ($this->context->parameters["cache"]) {

                $tools = $this->context->tools;
                $caching = $this->context->caching;
                $caching->deleteItem(NULL, array("tags" => "treeview"));
                $caching->deleteItem(array("content", $tools->getRealPath($tools->getAbsolutePath($actualdir))));
        }

        parent::getParent()->handleShowContent($actualdir);
    }

    public function render()
    {
        $actualdir = $this->context->system->getActualDir();
        $rootname = $this->context->tools->getRootName();

        $template = $this->template;
        $template->setFile(__DIR__ . '/Navigation.latte');
        $template->setTranslator($this->context->translator);

        if (!$actualdir)
            $template->items = $this->getNav($rootname);
        else
            $template->items = $this->getNav($actualdir);

        $this['locationForm']->setDefaults(array(
                    'actualdir' => $actualdir,
                    'location' => $actualdir
        ));

        $template->render();
    }

    public function createComponentLocationForm()
    {
        $translator = $this->context->translator;
        $form = new Form;
        $form->setTranslator($translator);
        $form->getElementPrototype()->class('fm-ajax');
        $form->addText('location');
        $form->addHidden('actualdir');
        $form->onSuccess[] = array($this, 'LocationFormSubmitted');

        return $form;
    }

    public function LocationFormSubmitted($form)
    {
        $val = $form->values;
        $path = $this->context->tools->getAbsolutePath($val['location']);

        if (file_exists($path))
            parent::getParent()->handleShowContent($val['location']);
        else {
            $translator = $this->context->translator;
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
        $rootname = $this->context->tools->getRootName();
        if ($actualdir === $rootname)
                $var[] = array(
                        "name" => $rootname,
                        "link" => parent::getParent()->link("showContent", $rootname)
                );
        else {
                $nav = explode("/", $actualdir);
                $path = "/";
                foreach ($nav as $item) {
                    if ($item) {
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