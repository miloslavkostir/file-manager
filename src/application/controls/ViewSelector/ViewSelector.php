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
 * ViewSelector control.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class ViewSelector extends \Ixtrum\FileManager\Application\Controls
{

    /**
     * Default render method
     */
    public function render()
    {
        $this->template->setFile(__DIR__ . "/ViewSelector.latte");
        $this->template->setTranslator($this->system->getService("translator"));
        $this->template->render();
    }

    /**
     * ChangeViewForm component factory
     *
     * @return \Nette\Application\UI\Form
     */
    protected function createComponentChangeViewForm()
    {
        $items = array(
            "large" => $this->system->getService("translator")->translate("Large images"),
            "small" => $this->system->getService("translator")->translate("Small images"),
            "list" => $this->system->getService("translator")->translate("List"),
            "details" => $this->system->getService("translator")->translate("Details")
        );

        $form = new Form;
        $form->addSelect("view", null, $items)
                ->setDefaultValue($this->view);
        $form->onSuccess[] = $this->changeViewFormSuccess;
        return $form;
    }

    /**
     * ChangeViewForm success event
     *
     * @param \Nette\Application\UI\Form $form Form instance
     */
    public function changeViewFormSuccess(Form $form)
    {
        $this->system->getService("session")->set("view", $form->values->view);
    }

}