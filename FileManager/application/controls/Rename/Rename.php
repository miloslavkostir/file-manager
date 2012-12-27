<?php

namespace Ixtrum\FileManager\Application\Controls;

use Nette\Application\UI\Form;

class Rename extends \Ixtrum\FileManager\Application\Controls
{

    public function render()
    {
        $this->template->setFile(__DIR__ . "/Rename.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->render();
    }

    /**
     * RenameForm component factory
     *
     * @return \Nette\Application\UI\Form
     */
    protected function createComponentRenameForm()
    {
        $form = new Form;
        $form->setTranslator($this->system->translator);
        $form->addText("newFilename", "New name")
                ->setRequired("New name required.");
        $form->addHidden("originalFilename");
        $form->addSubmit("send", "OK");

        if (isset($this->selectedFiles[0])) {
            $form["newFilename"]->setDefaultValue($this->selectedFiles[0]);
            $form["originalFilename"]->setDefaultValue($this->selectedFiles[0]);
        }

        $form->onSuccess[] = $this->renameFormSuccess;
        $form->onError[] = $this->parent->parent->onFormError;
        return $form;
    }

    /**
     * RenameForm success event
     *
     * @param \Nette\Application\UI\Form $form RenameForm
     *
     * @return void
     */
    public function renameFormSuccess(Form $form)
    {
        $actualDir = $this->getActualDir();
        $path = $this->getAbsolutePath($actualDir);

        if ($this->system->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Read-only mode enabled!"), "warning");
            return;
        }

        if ($form->values->newFilename === $form->values->originalFilename) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Names are the same!"), "warning");
            return;
        }

        if (file_exists($path . DIRECTORY_SEPARATOR . $form->values->newFilename)) {
            $this->parent->parent->flashMessage($this->system->translator->translate("'%s' already exist!", $form->values->newFilename), "warning");
            return;
        }

        if (!$this->isPathValid($actualDir, $form->values->originalFilename)) {
            $this->parent->parent->flashMessage($this->system->translator->translate("'%s' does not already exists!", $form->values->originalFilename), "error");
            return;
        }

        $origPath = $path . DIRECTORY_SEPARATOR . $form->values->originalFilename;
        if (is_dir($origPath)) {

            $newFilename = $this->system->filesystem->safeFoldername($form->values->newFilename);
            $this->system->thumbs->deleteDirThumbs($origPath);

            if ($this->system->parameters["cache"]) {

                $this->system->caching->deleteItem(array("content", $path));
                $this->system->caching->deleteItemsRecursive($origPath);
            }
        } else {

            $newFilename = $this->system->filesystem->safeFilename($form->values->newFilename);
            $this->system->thumbs->deleteThumb($origPath);

            if ($this->system->parameters["cache"]) {
                $this->system->caching->deleteItem(array("content", $path));
            }
        }

        if (rename($origPath, $path . DIRECTORY_SEPARATOR . $newFilename)) {

            $this->parent->parent->flashMessage($this->system->translator->translate("Successfully renamed to '%s'.", $newFilename));
            $this->system->session->clear("clipboard");
        } else {
            $this->parent->parent->flashMessage($this->system->translator->translate("An error occurred during %s rename!", $form->values->originalFilename), "error");
        }
    }

}