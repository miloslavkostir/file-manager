<?php

namespace Ixtrum\FileManager\Controls;

use Nette\Application\UI\Form;

class Navigation extends \Ixtrum\FileManager
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

        if (!$actualdir)
            $template->items = $this->getNav($rootname);
        else
            $template->items = $this->getNav($actualdir);

        $this['locationForm']->setDefaults(array('location' => $actualdir));

        $template->render();
    }

    protected function createComponentLocationForm()
    {
        $form = new Form;
        $form->setTranslator($this->context->translator);
        $form->addText('location');
        $form->onSuccess[] = callback($this, 'LocationFormSubmitted');

        return $form;
    }

    public function LocationFormSubmitted($form)
    {
        $val = $form->values;
        $path = $this->context->tools->getAbsolutePath($val['location']);

        if (is_dir($path))
                parent::getParent()->handleShowContent($val['location']);
        else {
                $folder = $val['location'];
                parent::getParent()->flashMessage("Folder $folder does not exists!", 'warning');
                parent::getParent()->handleShowContent($this->context->system->getActualDir());
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