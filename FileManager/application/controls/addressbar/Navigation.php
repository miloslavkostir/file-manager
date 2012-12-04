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
        if ($this->system->parameters["cache"]) {

            $this->system->caching->deleteItem(null, array("tags" => "treeview"));
            $this->system->caching->deleteItem(array(
                "content",
                $this->system->filesystem->getRealPath($this->system->filesystem->getAbsolutePath($this->getActualDir()))
            ));
        }
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . "/Navigation.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->items = $this->getNav($this->getActualDir());
        $this->template->render();
    }

    protected function createComponentLocationForm()
    {
        $form = new Form;
        $form->setTranslator($this->system->translator);
        $form->addText("location")
                ->setDefaultValue($this->getActualDir());
        $form->onSuccess[] = $this->locationFormSubmitted;
        return $form;
    }

    public function locationFormSubmitted($form)
    {
        $this->setActualDir($form->values->location);
    }

    /**
     * Create navigation structure
     *
     * @param string $dir Source directory in relative format
     *
     * @return array
     */
    protected function getNav($dir)
    {
        $var = array();
        $rootname = $this->system->filesystem->getRootName();
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