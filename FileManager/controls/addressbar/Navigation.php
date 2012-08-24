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
        $actualdir = $this->context->application->getActualDir();

        if ($this->context->parameters["cache"]) {

            $filesystem = $this->context->filesystem;
            $caching = $this->context->caching;
            $caching->deleteItem(NULL, array("tags" => "treeview"));
            $caching->deleteItem(array("content", $filesystem->getRealPath($filesystem->getAbsolutePath($actualdir))));
        }

        $this->parent->handleShowContent($actualdir);
    }

    public function render()
    {
        $actualdir = $this->context->application->getActualDir();
        $rootname = $this->context->filesystem->getRootName();

        $template = $this->template;
        $template->setFile(__DIR__ . '/Navigation.latte');
        $template->setTranslator($this->context->translator);

        if (!$actualdir) {
            $template->items = $this->getNav($rootname);
        } else {
            $template->items = $this->getNav($actualdir);
        }

        $this['locationForm']->setDefaults(array('location' => $actualdir));

        $template->render();
    }

    protected function createComponentLocationForm()
    {
        $form = new Form;
        $form->setTranslator($this->context->translator);
        $form->addText('location');
        $form->onSuccess[] = $this->locationFormSubmitted;
        return $form;
    }

    public function LocationFormSubmitted($form)
    {
        $val = $form->values;
        $path = $this->context->filesystem->getAbsolutePath($val['location']);

        if (is_dir($path)) {
            $this->parent->handleShowContent($val['location']);
        } else {
            $folder = $val['location'];
            $this->parent->flashMessage($this->context->translator->translate("Folder %s does not exist!", $folder), 'warning');
            $this->parent->handleShowContent($this->context->application->getActualDir());
        }
    }

    public function getNav($actualdir)
    {
        $var = array();
        $rootname = $this->context->filesystem->getRootName();
        if ($actualdir === $rootname)
            $var[] = array(
                "name" => $rootname,
                "link" => $this->parent->link("showContent", $rootname)
            );
        else {
            $nav = explode("/", $actualdir);
            $path = "/";
            foreach ($nav as $item) {
                if ($item) {
                    $path .= "$item/";
                    $var[] = array(
                        "name" => $item,
                        "link" => $this->parent->link("showContent", $path)
                    );
                }
            }
        }

        return $var;
    }

}