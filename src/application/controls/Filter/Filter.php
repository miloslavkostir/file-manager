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
 * Filter control.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class Filter extends \Ixtrum\FileManager\Application\Controls
{

    /**
     * Render control
     */
    public function render()
    {
        $this->template->setFile(__DIR__ . "/Filter.latte");
        $this->template->setTranslator($this->system->getService("translator"));
        $this->template->render();
    }

    /**
     * FilterForm component factory
     *
     * @return \Nette\Application\UI\Form
     */
    protected function createComponentFilterForm()
    {
        $form = new Form;
        $form->setTranslator($this->system->getService("translator"));
        $form->addText("phrase")
                ->setDefaultValue($this->system->getService("session")->get("mask"))
                ->setAttribute("placeholder", $this->system->getService("translator")->translate("Filter"));
        $form->onSuccess[] = $this->filterFormSuccess;
        return $form;
    }

    /**
     * FilterForm success event
     *
     * @param \Nette\Application\UI\Form $form Form instance
     */
    public function filterFormSuccess(Form $form)
    {
        $this->system->getService("session")->set("mask", $form->values->phrase);
    }

}