<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

namespace Ixtrum\FileManager\Application\Controls;

use Nette\Application\UI\Form;

/**
 * New directory control.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class NewDir extends \Ixtrum\FileManager\Application\Controls
{

    /**
     * Render control
     */
    public function render()
    {
        $this->template->setFile(__DIR__ . "/NewDir.latte");
        $this->template->setTranslator($this->system->getService("translator"));
        $this->template->render();
    }

    /**
     * NewDirForm component factory
     *
     * @return \Nette\Application\UI\Form
     */
    protected function createComponentNewDirForm()
    {
        $form = new Form;
        $form->setTranslator($this->system->getService("translator"));
        $form->addText("name", "Name")
                ->setRequired("Directory name is required.");
        $form->addSubmit("send", "Create");
        $form->onSuccess[] = $this->newDirFormSuccess;
        $form->onError[] = $this->parent->parent->onFormError;
        return $form;
    }

    /**
     * NewDirForm success event
     *
     * @param \Nette\Application\UI\Form $form Form instance
     *
     * @return void
     */
    public function newDirFormSuccess(Form $form)
    {
        if ($this->system->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->system->getService("translator")->translate("Read-only mode enabled!"), "warning");
            return;
        }

        if (!$this->isPathValid($this->getActualDir())) {
            $this->parent->parent->flashMessage($this->system->getService("translator")->translate("Directory '%s' already does not exist!", $this->getActualDir()), "warning");
            return;
        }

        $dirname = $this->system->getService('filesystem')->safeDirname($form->values->name);
        if (!$dirname) {
            $this->parent->parent->flashMessage($this->system->getService("translator")->translate("Directory name '%s' can not be used, not allowed characters used!", $form->values->name), "warning");
            return;
        }

        $targetPath = $this->getAbsolutePath($this->getActualDir()) . DIRECTORY_SEPARATOR . $dirname;
        if (is_dir($targetPath) AND in_array($dirname, $this->system->parameters["hiddenDirs"])) {
            $this->parent->parent->flashMessage($this->system->getService("translator")->translate("Destination directory '%s' is reserved by system!", $dirname), "warning");
            return;

        } 
		
		if (is_dir($targetPath)) {
            $this->parent->parent->flashMessage($this->system->getService("translator")->translate("Destination directory '%s' already exists!", $dirname), "warning");
            return;
        }

        if (!$this->system->getService('filesystem')->mkdir($targetPath)) {
            $this->parent->parent->flashMessage($this->system->getService("translator")->translate("An error occurred, can not create directory '%s'.", $dirname), "error");
            return;
        }

        if ($this->system->parameters["cache"]) {

            $this->system->getService('caching')->deleteItem(array(
                "content",
                $this->getAbsolutePath($this->getActualDir())
            ));
            $this->system->getService('caching')->deleteItem(null, array("tags" => "treeview"));
        }

        $this->parent->parent->flashMessage($this->system->getService("translator")->translate("Directory '%s' successfully created.", $dirname));
    }

}