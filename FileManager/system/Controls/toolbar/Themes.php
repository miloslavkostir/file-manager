<?php

namespace Ixtrum\FileManager\Controls;

class Themes extends \Ixtrum\FileManager
{

    public function render()
    {
        $this->template->setFile(__DIR__ . "/Themes.latte");
        $this->template->setTranslator($this->context->translator);
        $session = $this->presenter->context->session->getSection("file-manager");
        if ($session->theme) {
            $this["themeForm"]->setDefaults(array("theme" => $session->theme));
        } else {
            $this["themeForm"]->setDefaults(array("theme" => "default"));
        }

        $this->template->render();
    }

    protected function createComponentThemeForm()
    {
        $form = new \Nette\Application\UI\Form;
        $form->addSelect("theme", NULL, $this->loadThemes())
                ->setAttribute("onchange", "submit()");
        $form->onSuccess[] = $this->themeFormSubmitted;
        return $form;
    }

    public function themeFormSubmitted(\Nette\Application\UI\Form $form)
    {
        $session = $this->presenter->context->session->getSection("file-manager");
        $session->theme = $form->values->theme;
        $this->redirect("this");
    }

    /**
     * Load themes from theme dir
     *
     * @return array
     */
    private function loadThemes()
    {
        $themes = array();
        $files = \Nette\Utils\Finder::findDirectories("*")->in($this->context->parameters["resPath"] . "themes");
        foreach ($files as $file) {

            // Get theme name
            $themeFile = $file->getPathname() . "/theme.txt";
            if (file_exists($themeFile)) {
                $themeName = file_get_contents($themeFile);
            } else {
                $themeName = $file->getFilename();
            }

            $themes[$file->getFilename()] = $themeName;
        }
        return $themes;
    }

}