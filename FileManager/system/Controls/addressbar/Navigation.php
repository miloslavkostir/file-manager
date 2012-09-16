<?php

namespace Ixtrum\FileManager\Controls;

use Nette\Application\UI\Form;

class Navigation extends \Ixtrum\FileManager
{

    public function handleRefreshContent()
    {
        if ($this->context->parameters["cache"]) {

            $this->context->caching->deleteItem(null, array("tags" => "treeview"));
            $this->context->caching->deleteItem(array(
                "content",
                $this->context->filesystem->getRealPath($this->context->filesystem->getAbsolutePath($this->actualDir))
            ));
        }
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . "/Navigation.latte");
        $this->template->setTranslator($this->context->translator);
        $this->template->items = $this->getNav($this->actualDir);
        $this->template->render();
    }

    protected function createComponentLocationForm()
    {
        $form = new Form;
        $form->setTranslator($this->context->translator);
        $form->addText("location")
                ->setDefaultValue($this->actualDir);
        $form->onSuccess[] = $this->locationFormSubmitted;
        return $form;
    }

    public function locationFormSubmitted($form)
    {
        $path = $this->context->filesystem->getAbsolutePath($form->values->location);
        if (is_dir($path)) {
            $this->parent->parent->handleShowContent($form->values->location);
        } else {
            $this->parent->parent->flashMessage($this->context->translator->translate("Folder %s does not exist!", $form->values->location, "warning"));
            $this->parent->parent->handleShowContent($this->actualDir);
        }
    }

    public function getNav($dir)
    {
        $var = array();
        $rootname = $this->context->filesystem->getRootName();
        if ($dir === $rootname)
            $var[] = array(
                "name" => $rootname,
                "link" => $this->parent->parent->link("showContent", $rootname)
            );
        else {
            $nav = explode("/", $dir);
            $path = "/";
            foreach ($nav as $item) {
                if ($item) {
                    $path .= "$item/";
                    $var[] = array(
                        "name" => $item,
                        "link" => $this->parent->parent->link("showContent", $path)
                    );
                }
            }
        }

        return $var;
    }

}