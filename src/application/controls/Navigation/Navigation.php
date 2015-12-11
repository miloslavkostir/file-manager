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

use Nette\Application\UI\Form,
    Ixtrum\FileManager\Application\FileSystem;

/**
 * Navigatior control.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class Navigation extends \Ixtrum\FileManager\Application\Controls
{

    /**
     * Go to dir
     *
     * @param string $dir Dir name
     */
    public function handleOpenDir($dir)
    {
        $this->setActualDir($dir);
    }

    /**
     * Refresh content
     */
    public function handleRefreshContent()
    {
        if ($this->system->parameters["cache"]) {

            $this->system->getService("caching")->deleteItem(null, array("tags" => "treeview"));
            $this->system->getService("caching")->deleteItem(array(
                "content",
                $this->getAbsolutePath($this->getActualDir())
            ));
        }
    }

    /**
     * Render control
     */
    public function render()
    {
        $this->template->setFile(__DIR__ . "/Navigation.latte");
        $this->template->setTranslator($this->system->getService("translator"));
        $this->template->items = $this->getNav($this->getActualDir());
        $this->template->render();
    }

    /**
     * LocationForm component factory
     *
     * @return \Nette\Application\UI\Form
     */
    protected function createComponentLocationForm()
    {
        $form = new Form;
        $form->setTranslator($this->system->getService("translator"));
        $form->addText("location")
                ->setDefaultValue($this->getActualDir());
        $form->onSuccess[] = $this->locationFormSuccess;
        return $form;
    }

    /**
     * LocationForm success event
     *
     * @param \Nette\Application\UI\Form $form Form instance
     */
    public function locationFormSuccess(Form $form)
    {
		if (!in_array(preg_replace('/^\/(.*)/', '\\1', $this->getActualDir()), $this->system->parameters["hiddenDirs"])) {
			$this->setActualDir($form->values->location);
		}
    }

    /**
     * Create navigation structure
     *
     * @param string $dir Source directory in relative format
     *
     * @return array
     */
    protected function getNav($dir)
    {
        $var = array();
        $rootname = FileSystem::getRootName();
        if ($dir === $rootname)
            $var[] = array(
                "name" => $rootname,
                "link" => $this->link("openDir", $rootname)
            );
        else {
            $nav = explode("/", $dir);
            $path = "/";
            foreach ($nav as $item) {
                if ($item) {
                    $path .= "$item/";
                    $var[] = array(
                        "name" => $item,
                        "link" => $this->link("openDir", $path)
                    );
                }
            }
        }

        return $var;
    }

}