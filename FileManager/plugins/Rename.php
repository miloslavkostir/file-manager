<?php

namespace Ixtrum\FileManager\Plugins;

use Nette\Application\UI\Form;

class Rename extends \Ixtrum\FileManager
{

    /** @var boolean */
    public $contextPlugin = true;

    /** @var string */
    public $files;

    /** @var string */
    public $title = "Rename";

    public function __construct($config)
    {
        parent::__construct($config);
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . "/Rename.latte");
        $template->setTranslator($this->context->translator);
        $template->origFile = $this->files;

        $this["renameForm"]->setDefaults(array(
            "new_filename" => $this->files,
            "orig_filename" => $this->files,
        ));

        $template->render();
    }

    protected function createComponentRenameForm()
    {
        $form = new Form;
        $form->setTranslator($this->context->translator);
        $form->addText("new_filename", "New name")
            ->setRequired("New name required.");
        $form->addHidden("orig_filename");
        $form->addSubmit("send", "OK");
        $form->onSuccess[] = $this->renameFormSubmitted;
        $form->onError[] = $this->parent->parent->onFormError;
        return $form;
    }

    public function renameFormSubmitted($form)
    {
        $values = $form->getValues();
        $actualdir = $this->context->application->getActualDir();
        $path = $this->context->filesystem->getAbsolutePath($actualdir);

        if ($this->context->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->context->translator->translate("Read-only mode enabled!"), "warning");
        } elseif ($values["new_filename"] == $values["orig_filename"]) {
            $this->parent->parent->flashMessage($this->context->translator->translate("New name can not be the same!"), "warning");
        } elseif (file_exists($path . $values["new_filename"])) {
            $this->parent->parent->flashMessage($this->context->translator->translate("The name %s was already used.", $values["new_filename"]), "warning");
        } elseif (!file_exists($path . $values["orig_filename"])) {
            $this->parent->parent->flashMessage($this->context->translator->translate("File/folder %s does not already exists!", $values["orig_filename"]), "error");
        } else {

            $origPath = $path . $values["orig_filename"];
            if (is_dir($this->context->filesystem->getRealPath($origPath))) {

                $new_filename = $this->context->filesystem->safeFoldername($values["new_filename"]);
                $this->context->thumbs->deleteDirThumbs($origPath);

                if ($this->context->parameters["cache"]) {

                    $this->context->caching->deleteItem(array(
                        "content",
                        $this->context->filesystem->getRealPath($path)
                    ));
                    $this->context->caching->deleteItemsRecursive($origPath);
                }
            } else {

                $new_filename = $this->context->filesystem->safeFilename($values["new_filename"]);
                $this->context->thumbs->deleteThumb($this->context->filesystem->getRealPath($origPath));

                if ($this->context->parameters["cache"]) {

                    $this->context->caching->deleteItem(array(
                        "content",
                        $this->context->filesystem->getRealPath($path)
                    ));
                }
            }

            if (rename($origPath, $path . $new_filename)) {

                $this->parent->parent->flashMessage($this->context->translator->translate("Successfully renamed to %s.", $new_filename), "info");
                $this->context->application->clearClipboard();
            } else {
                $this->parent->parent->flashMessage($this->context->translator->translate("An error occurred during %s renaming!", $values["orig_filename"]), "error");
            }
        }

        $this->parent->parent->handleShowContent($actualdir);
    }

}