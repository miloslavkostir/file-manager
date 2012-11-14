<?php

namespace Ixtrum\FileManager\Application\Controls;

class Themes extends \Ixtrum\FileManager
{

    /**
     * Default render method
     */
    public function render()
    {
        $this->template->setFile(__DIR__ . "/Themes.latte");
        $this->template->setTranslator($this->context->translator);

        $theme = $this->context->session->get("theme");
        if (empty($theme)) {
            $this->getComponent("themeForm")->setDefaults(array("theme" => "default"));
        } else {
            $this->getComponent("themeForm")->setDefaults(array("theme" => $theme));
        }

        $this->template->render();
    }

    /**
     * ThemeForm component factory
     *
     * @return \Nette\Application\UI\Form
     */
    protected function createComponentThemeForm()
    {
        $themeDir = $this->context->parameters["wwwDir"] . $this->context->parameters["resDir"] . "themes";
        $form = new \Nette\Application\UI\Form;
        $form->addSelect("theme", null, $this->loadThemes($themeDir))
                ->setAttribute("onchange", "submit()");
        $form->onSuccess[] = $this->themeFormSuccess;
        return $form;
    }

    /**
     * ThemeForm on success event
     *
     * @param \Nette\Application\UI\Form $form Form
     */
    public function themeFormSuccess(\Nette\Application\UI\Form $form)
    {
        $this->context->session->set("theme", $form->values->theme);
        $this->redirect("this");
    }

    /**
     * Load themes from theme dir
     *
     * @param string $themeDir Dir with themes
     *
     * @return array
     */
    public function loadThemes($themeDir)
    {
        $themes = array();
        if (!is_dir($themeDir)) {
            return $themes;
        }

        $dirs = \Nette\Utils\Finder::findDirectories("*")->in($themeDir);
        foreach ($dirs as $dir) {
            $themes[$dir->getFilename()] = ucfirst($dir->getFilename());
        }
        return $themes;
    }

}