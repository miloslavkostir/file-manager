<?php

namespace Ixtrum\FileManager\Application\Controls;

use Nette\Application\UI\Form;

class NewFolder extends \Ixtrum\FileManager\Application\Controls
{

    public function render()
    {
        $this->template->setFile(__DIR__ . "/NewFolder.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->render();
    }

    protected function createComponentNewFolderForm()
    {
        $form = new Form;
        $form->setTranslator($this->system->translator);
        $form->addText("name", "Name:")
                ->setRequired("Folder name required.");
        $form->addSubmit("send", "Create");
        $form->onSuccess[] = $this->newFolderFormSuccess;
        $form->onError[] = $this->parent->parent->onFormError;
        return $form;
    }

    public function newFolderFormSuccess(Form $form)
    {
        if ($this->system->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Read-only mode enabled!"), "warning");
            return;
        }

        if (!$this->isPathValid($this->getActualDir())) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Folder %s already does not exist!", $this->getActualDir()), "warning");
            return;
        }

        $foldername = $this->system->filesystem->safeFoldername($form->values->name);
        if (!$foldername) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Folder name '%s' can not be used, not allowed characters used!", $form->values->name), "warning");
            return;
        }

        $targetPath = $this->getAbsolutePath($this->getActualDir()) . DIRECTORY_SEPARATOR . $foldername;
        if (is_dir($targetPath)) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Destination folder '%s' already exists!", $foldername), "warning");
            return;
        }

        if (!$this->system->filesystem->mkdir($targetPath)) {
            $this->parent->parent->flashMessage($this->system->translator->translate("An unkonwn error occurred during folder %s creation.", $foldername));
            return;
        }

        if ($this->system->parameters["cache"]) {

            $this->system->caching->deleteItem(array(
                "content",
                $this->getAbsolutePath($this->getActualDir())
            ));
            $this->system->caching->deleteItem(null, array("tags" => "treeview"));
        }

        $this->parent->parent->flashMessage($this->system->translator->translate("Folder '%s' successfully created.", $foldername));
    }

}