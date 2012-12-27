<?php

namespace Ixtrum\FileManager\Application\Controls;

use Ixtrum\FileManager\Application\FileSystem\Finder;

class Treeview extends \Ixtrum\FileManager\Application\Controls
{

    public function handleOpenDir($dir)
    {
        $this->setActualDir($dir);
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . "/Treeview.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->treeview = $this->loadData();
        $this->template->render();
    }

    private function generateTree($dirs, $superior)
    {
        $html = "<ul>";
        foreach ($dirs as $key => $value) {

            $html .= "<li>";
            $html .= '<span class="fm-droppable" data-move-url="' . $this->getComponent("control-Content")->link("move") . '" data-targetdir="' . $superior . '/' . $key . '/' . '">';
            $html .= '<a href="' . $this->link("openDir", "$superior/$key/") . '" class="treeview-folder fm-ajax fm-folder-icon" title="' . $superior . '/' . $key . '/">';
            $html .= $key;
            $html .= '</a></span>';

            if (count($dirs[$key]) > 0) {
                $subSuperior = "$superior/$key";
                $html .= $this->generateTree($dirs[$key], $subSuperior);
            }

            $html .= "</li>";
        }

        $html .= "</ul>";

        return $html;
    }

    private function getDirTree($dir)
    {
        $x = array();
        $dirs = Finder::findDirectories("*")->in($dir);

        foreach ($dirs as $dir) {
            $x[$dir->getFilename()] = $this->getDirTree($dir->getPathName());
        }

        return $x;
    }

    private function generateTreeview()
    {
        $dirs = $this->getDirTree($this->system->parameters["uploadroot"]);
        $rootname = $this->system->filesystem->getRootName();

        $output = '<ul class="filetree">';
        $output .= '<span class="fm-droppable" data-move-url="' . $this->getComponent("control-Content")->link("move") . '" data-targetdir="' . $rootname . '">';
        $output .= '<a href="' . $this->link("openDir", $rootname) . '" class="fm-ajax treeview-folder fm-root-icon" title="' . $rootname . '">';
        $output .= $rootname;
        $output .= '</a></span>';
        $output .= $this->generateTree($dirs, null);
        $output .= '</ul>';

        return $output;
    }

    /**
     * Load data
     *
     * @return string
     */
    public function loadData()
    {
        if ($this->system->parameters["cache"]) {

            $path = $this->system->parameters["uploadroot"];
            $cacheData = $this->system->caching->getItem($path);
            if (!$cacheData) {

                $output = $this->generateTreeview();
                $this->system->caching->saveItem($path, $output, array("tags" => array("treeview")));
                return $output;
            } else {
                return $cacheData;
            }
        } else {
            return $this->generateTreeview();
        }
    }

}