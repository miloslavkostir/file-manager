<?php

namespace Netfileman;

use Nette\Application\UI\Form;

/**
 * TODO don't use hidden, actualdir is stored in session
 */
class NewFolder extends Netfileman
{
        public function __construct($userConfig)
        {
                parent::__construct($userConfig);
        }


        public function render()
        {
                $actualdir = $this->context->system->getActualDir();

                $template = $this->template;
                $template->setFile(__DIR__ . "/NewFolder.latte");
                $template->setTranslator($this->context->translator);
                $template->actualdir = $actualdir;

                $this["newFolderForm"]->setDefaults(array("actualdir" => $actualdir));

                $template->render();
        }


        public function  createComponentNewFolderForm()
        {
                $translator = $this->context->translator;

                $form = new Form;
                $form->setTranslator($translator);
                $form->getElementPrototype()->class("fm-ajax");
                $form->addText("foldername", "Name of the new folder:")
                        ->addRule(Form::FILLED, "You must fill name of new folder.");
                $form->addHidden("actualdir");
                $form->addSubmit("send", "Create");
                $form->onSuccess[] = array($this, "NewFolderFormSubmitted");

                return $form;
        }


        public function NewFolderFormSubmitted($form)
        {
                $translator = $this->context->translator;
                $values = $form->getValues();

                if ($this->context->parameters["readonly"])
                        parent::getParent()->flashMessage($translator->translate("File manager is in read-only mode"), "warning");
                else {

                        if ($this->context->tools->validPath($values["actualdir"])) {

                                    $foldername = $this->context->files->safe_foldername($values["foldername"]);

                                    if ($values['actualdir'] == $this->context->tools->getRootName()) {

                                            $target_dir = $this->context->parameters["uploadroot"] . $this->context->parameters["uploadpath"] . $foldername;
                                            $actualdir = "/" . $foldername . "/";
                                    }
                                    else {

                                            $target_dir = $this->context->parameters['uploadroot'] . substr($this->context->parameters["uploadpath"], 0, -1) . $values['actualdir'] . $foldername;
                                            $actualdir = $values["actualdir"]  . $foldername . "/";
                                    }

                                    if ($foldername == "")
                                            parent::getParent()->flashMessage($translator->translate("Folder name can not be used. Illegal chars used") . ' \ / : * ? " < > | ..', "warning");
                                    else {

                                            if (file_exists($target_dir))
                                                    parent::getParent()->flashMessage($translator->translate("Folder name already exist. Try choose another"), "warning");
                                            else {
                                                    $oldumask = umask(0);
                                                    if (mkdir($target_dir, 0777)) {

                                                            parent::getParent()->flashMessage($translator->translate("Folder successfully created"), "info");

                                                            if ($this->context->parameters["cache"]) {

                                                                    $caching = $this->context->caching;
                                                                    $caching->deleteItem(NULL, array("tags" => "treeview"));
                                                            }

                                                            parent::getParent()->handleShowContent($values["actualdir"]);   //   TODO replace actualdir with redirect to new created folder
                                                    } else
                                                            parent::getParent()->flashMessage($translator->translate("An unkonwn error occurred during folder creation"), "info");

                                                    umask($oldumask);
                                            }
                                    }
                        } else
                                parent::getParent()->flashMessage($translator->translate("Folder %s already does not exist!", $values["actualdir"]), "warning");
                }

                parent::getParent()->handleShowContent($values['actualdir']);
        }
}