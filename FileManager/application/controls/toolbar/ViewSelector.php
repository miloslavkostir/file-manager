<?php

namespace Ixtrum\FileManager\Application\Controls;

use Nette\Application\UI\Form;

class ViewSelector extends \Ixtrum\FileManager
{

    public function render()
    {
        $this->template->setFile(__DIR__ . "/ViewSelector.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->render();
    }

    protected function createComponentChangeViewForm()
    {
        $items = array(
            "large" => $this->system->translator->translate("Large images"),
            "small" => $this->system->translator->translate("Small images"),
            "list" => $this->system->translator->translate("List"),
            "details" => $this->system->translator->translate("Details")
        );

        $form = new Form;
        $form->addSelect("view", null, $items)
                ->setDefaultValue($this->view);
        $form->onSuccess[] = $this->changeViewFormSubmitted;
        return $form;
    }

    public function changeViewFormSubmitted($form)
    {
        $this->system->session->set("view", $form->values->view);
    }

}