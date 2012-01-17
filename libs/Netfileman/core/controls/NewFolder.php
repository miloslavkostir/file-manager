<?php

namespace Netfileman;


class NewFolder extends Netfileman
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
                $form->getElementPrototype()->class("fm-ajax");
                $form->addText("foldername", "Name:")
                        ->setRequired("You must fill name of new folder.");
                $form->addSubmit("send", "Create");
                $form->onSuccess[] = callback($this, "NewFolderFormSubmitted");

                return $form;
        }


        public function NewFolderFormSubmitted($form)
        {
                $values = $form->values;
                $translator = $this->context->translator;
                $actualdir = $this->context->system->getActualDir();

                if ($this->context->parameters["readonly"])
                        parent::getParent()->flashMessage($translator->translate("File manager is in read-only mode"), "warning");
                else {

                        if ($this->context->tools->validPath($actualdir)) {

                                $foldername = $this->context->files->safe_foldername($values["foldername"]);
                                if (!$foldername)
                                        parent::getParent()->flashMessage($translator->translate("Folder name can not be used. Illegal chars used") . ' \ / : * ? " < > | ..', "warning");
                                else {

                                        $target_dir = $this->context->tools->getAbsolutePath($actualdir) . $foldername;
                                        if (file_exists($target_dir))
                                                parent::getParent()->flashMessage($translator->translate("Folder name already exist. Try choose another"), "warning");
                                        else {

                                                if ($this->context->tools->mkdir($target_dir)) {

                                                        if ($this->context->parameters["cache"]) {

                                                                $caching = $this->context->caching;
                                                                $caching->deleteItem(NULL, array("tags" => "treeview"));
                                                        }

                                                        parent::getParent()->flashMessage($translator->translate("Folder successfully created"), "info");
                                                        parent::getParent()->handleShowContent($actualdir);
                                                } else
                                                        parent::getParent()->flashMessage($translator->translate("An unkonwn error occurred during folder creation"), "info");
                                        }
                                }
                        } else
                                parent::getParent()->flashMessage($translator->translate("Folder %s already does not exist!", $actualdir), "warning");
                }

                parent::getParent()->handleShowContent($actualdir);
        }
}