<?php

namespace Ixtrum\FileManager\Plugins;

class NewFolder extends \Ixtrum\FileManager
{

    /** @var string */
    public $title = "New folder";

    /** @var bool */
    public $toolbarPlugin = true;

    public function __construct($config)
    {
        parent::__construct($config);
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . "/NewFolder.latte");
        $template->setTranslator($this->context->translator);
        $template->render();
    }

    protected function createComponentNewFolderForm()
    {
        $form = new \Nette\Application\UI\Form;
        $form->setTranslator($this->context->translator);
        $form->addText("name", "Name:")
            ->setRequired("Folder name required.");
        $form->addSubmit("send", "Create");
        $form->onSuccess[] = $this->newFolderFormSubmitted;
        $form->onError[] = $this->parent->parent->onFormError;
        return $form;
    }

    public function newFolderFormSubmitted($form)
    {
        $values = $form->values;
        $actualdir = $this->context->application->getActualDir();

        if ($this->context->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->context->translator->translate("Read-only mode enabled!"), "warning");
        } else {

            if ($this->context->filesystem->validPath($actualdir)) {

                $foldername = $this->context->filesystem->safeFoldername($values->name);
                if (!$foldername) {
                    $this->parent->parent->flashMessage($this->context->translator->translate("Folder name '%s' can not be used - not allowed characters used.", $values->name), "warning");
                } else {

                    $target_dir = $this->context->filesystem->getAbsolutePath($actualdir) . $foldername;
                    if (is_dir($target_dir)) {
                        $this->parent->parent->flashMessage($this->context->translator->translate("Target name %s already exists!", $foldername), "warning");
                    } else {

                        if ($this->context->filesystem->mkdir($target_dir)) {

                            if ($this->context->parameters["cache"]) {

                                $this->context->caching->deleteItem(array(
                                    "content",
                                    $this->context->filesystem->getRealPath($this->context->filesystem->getAbsolutePath($actualdir))
                                ));
                                $this->context->caching->deleteItem(NULL, array("tags" => "treeview"));
                            }

                            $this->parent->parent->flashMessage($this->context->translator->translate("Folder %s successfully created", $foldername), "info");
                        } else {
                            $this->parent->parent->flashMessage($this->context->translator->translate("An unkonwn error occurred during folder %s creation", $foldername), "info");
                        }
                    }
                }
            } else {
                $this->parent->parent->flashMessage($this->context->translator->translate("Folder %s already does not exist!", $actualdir), "warning");
            }
        }

        $this->parent->parent->handleShowContent($actualdir);
    }

}