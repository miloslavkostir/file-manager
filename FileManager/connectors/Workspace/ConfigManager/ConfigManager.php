<?php

namespace Ixtrum\Workspace\Applications\FileManager\Controls;

class ConfigManager extends \Nette\Application\UI\Control
{

    /** @var string */
    private $configPath;

    /**
     * Constructor
     *
     * @param string $onfigPath Path to config file
     */
    public function __construct($configPath)
    {
        if (!is_file($configPath)) {
            throw new \Exception("Config file '$configPath' not found!");
        }
        $this->configPath;
    }

    protected function createComponentConfigForm()
    {
        $fileManager = new FileManager($this->context, array("uploadroot" => $this->context->parameters["storage"]["uploadDir"])); // @todo

        $form = new Form;
        $form->addText("uploadroot", "Upload root")
                ->setPrompt("-- select item --");
        $form->addSelect("lang", "Language", $fileManager->getLanguages());
        $form->addCheckbox("quota", "Quota");
        $form->addText("quotaLimit", "Quota limit");
        $form->addSelect("role", "Role", $roles)
                ->setPrompt("-- select item --")
                ->setRequired("Please set item '%label'");
        $form->addCheckbox("readonly", "Read-only");
        $form->addCheckbox("cache", "Enable cache");
        $form->addSubmit("save", "Save");

        // Aditional rules
        $form["quotaLimit"]->addConditionOn($form["quota"], Form::EQUAL, true)
                ->addRule(Form::FILLED, "Please set item '%label'")
                ->addRule(Form::INTEGER, '%label must be integer');

        $form->onSuccess[] = $this->successConfigForm;
        return $form;
    }

    /**
     * ConfigForm on success event
     *
     * @param \Nette\Application\UI\Form $form
     */
    public function successConfigForm(Form $form)
    {

    }

}