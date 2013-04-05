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

use Ixtrum\FileManager\Application\FileSystem\Finder,
    Nette\Application\UI\Form;

/**
 * Themes control.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class Themes extends \Ixtrum\FileManager\Application\Controls
{

    /**
     * Default render method
     */
    public function render()
    {
        $this->template->setFile(__DIR__ . "/Themes.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->render();
    }

    /**
     * ThemeForm component factory
     *
     * @return \Nette\Application\UI\Form
     */
    protected function createComponentThemeForm()
    {
        $default = $this->system->session->get("theme");
        if (empty($default)) {
            $default = "default";
            $this->system->session->set("theme", $default);
        }

        $themeDir = $this->system->parameters["resDir"] . "/themes";
        $form = new Form;
        $form->addSelect("theme", null, $this->loadThemes($themeDir))
                ->setDefaultValue($default);
        $form->onSuccess[] = $this->themeFormSuccess;
        return $form;
    }

    /**
     * ThemeForm on success event
     *
     * @param \Nette\Application\UI\Form $form Form instance
     */
    public function themeFormSuccess(Form $form)
    {
        $this->system->session->set("theme", $form->values->theme);
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

        $dirs = Finder::findDirectories("*")->in($themeDir);
        foreach ($dirs as $dir) {
            $themes[$dir->getFilename()] = ucfirst($dir->getFilename());
        }
        return $themes;
    }

}