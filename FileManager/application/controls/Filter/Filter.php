<?php

namespace Ixtrum\FileManager\Application\Controls;

use Nette\Application\UI\Form;

class Filter extends \Ixtrum\FileManager\Application\Controls
{

    public function render()
    {
        $this->template->setFile(__DIR__ . "/Filter.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->render();
    }

    protected function createComponentFilterForm()
    {
        $form = new Form;
        $form->setTranslator($this->system->translator);
        $form->addText("phrase")
                ->setDefaultValue($this->system->session->get("mask"))
                ->setAttribute("placeholder", $this->system->translator->translate("Filter"));
        $form->onSuccess[] = $this->filterFormSuccess;
        return $form;
    }

    public function filterFormSuccess($form)
    {
        $this->system->session->set("mask", $form->values->phrase);
    }

}