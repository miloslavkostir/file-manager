<?php

namespace Ixtrum\FileManager\Controls;


class NewFolder extends \Ixtrum\FileManager
{
        public function __construct($userConfig)
        {
                parent::__construct($userConfig);
        }


        public function render()
        {
                $template = $this->template;
                $template->setFile(__DIR__ . "/NewFolder.latte");
                $template->setTranslator($this->context->translator);
                $template->render();
        }


        protected function  createComponentNewFolderForm()
        {
                $form = new \Nette\Application\UI\Form;
                $form->setTranslator($this->context->translator);
                $form->addText("name", "Name:")
                        ->setRequired("Folder name required.");
                $form->addSubmit("send", "Create");
                $form->onSuccess[] = callback($this, "NewFolderFormSubmitted");

                return $form;
        }


        public function NewFolderFormSubmitted($form)
        {
                $values = $form->values;
                $actualdir = $this->context->system->getActualDir();

                if ($this->context->parameters["readonly"])
                        parent::getParent()->flashMessage($this->context->translator->translate("Read-only mode enabled!"), "warning");
                else {

                        $tools = $this->context->tools;
                        if ($tools->validPath($actualdir)) {

                                $foldername = $this->context->files->safe_foldername($values->name);
                                if (!$foldername)
                                        parent::getParent()->flashMessage($this->context->translator->translate("Folder name '%s' can not be used - not allowed characters used.", $values->name), "warning");
                                else {

                                        $target_dir = $this->context->tools->getAbsolutePath($actualdir) . $foldername;
                                        if (is_dir($target_dir))
                                                parent::getParent()->flashMessage($this->context->translator->translate("Target name %s already exists!", $foldername), "warning");
                                        else {

                                                if ($this->context->files->mkdir($target_dir)) {

                                                        if ($this->context->parameters["cache"]) {

                                                                $caching = $this->context->caching;
                                                                $caching->deleteItem(array("content", $tools->getRealPath($tools->getAbsolutePath($actualdir))));
                                                                $caching->deleteItem(NULL, array("tags" => "treeview"));
                                                        }

                                                        parent::getParent()->flashMessage($this->context->translator->translate("Folder %s successfully created", $foldername), "info");
                                                        parent::getParent()->handleShowContent($actualdir);
                                                } else
                                                        parent::getParent()->flashMessage($this->context->translator->translate("An unkonwn error occurred during folder %s creation", $foldername), "info");
                                        }
                                }
                        } else
                                parent::getParent()->flashMessage($this->context->translator->translate("Folder %s already does not exist!", $actualdir), "warning");
                }

                parent::getParent()->handleShowContent($actualdir);
        }
}