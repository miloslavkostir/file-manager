<?php

namespace Ixtrum\FileManager\Controls;

use Nette\Application\UI\Form;

class ViewSelector extends \Ixtrum\FileManager
{

    public function render()
    {
        $this->template->setFile(__DIR__ . "/ViewSelector.latte");
        $this->template->setTranslator($this->context->translator);
        $this->template->render();
    }

    protected function createComponentChangeViewForm()
    {
        $items = array(
            "large" => $this->context->translator->translate("Large images"),
            "small" => $this->context->translator->translate("Small images"),
            "list" => $this->context->translator->translate("List"),
            "details" => $this->context->translator->translate("Details")
        );

        $form = new Form;
        $form->addSelect("view", null, $items)
                ->setDefaultValue($this->view);
        $form->onSuccess[] = $this->changeViewFormSubmitted;
        return $form;
    }

    public function changeViewFormSubmitted($form)
    {
        $this->context->session->set("view", $form->values->view);
    }

}