<?php

namespace Ixtrum\FileManager\Application\Controls;

use Nette\Application\UI\Form;

class Navigation extends \Ixtrum\FileManager
{

    public function handleOpenDir($dir)
    {
        $this->setActualDir($dir);
    }

    public function handleRefreshContent()
    {
        if ($this->context->parameters["cache"]) {

            $this->context->caching->deleteItem(null, array("tags" => "treeview"));
            $this->context->caching->deleteItem(array(
                "content",
                $this->context->filesystem->getRealPath($this->context->filesystem->getAbsolutePath($this->getActualDir()))
            ));
        }
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . "/Navigation.latte");
        $this->template->setTranslator($this->context->translator);
        $this->template->items = $this->getNav($this->getActualDir());
        $this->template->render();
    }

    protected function createComponentLocationForm()
    {
        $form = new Form;
        $form->setTranslator($this->context->translator);
        $form->addText("location")
                ->setDefaultValue($this->getActualDir());
        $form->onSuccess[] = $this->locationFormSubmitted;
        return $form;
    }

    public function locationFormSubmitted($form)
    {
        $this->setActualDir($form->values->location);
    }

    public function getNav($dir)
    {
        $var = array();
        $rootname = $this->context->filesystem->getRootName();
        if ($dir === $rootname)
            $var[] = array(
                "name" => $rootname,
                "link" => $this->link("openDir", $rootname)
            );
        else {
            $nav = explode("/", $dir);
            $path = "/";
            foreach ($nav as $item) {
                if ($item) {
                    $path .= "$item/";
                    $var[] = array(
                        "name" => $item,
                        "link" => $this->link("openDir", $path)
                    );
                }
            }
        }

        return $var;
    }

}