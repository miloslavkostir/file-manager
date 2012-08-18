<?php

namespace Ixtrum\FileManager\Controls;

use Nette\Utils\Finder,
    Nette\DI\Container;

class Treeview extends \Ixtrum\FileManager
{

    public function __construct($userConfig)
    {
        parent::__construct($userConfig);
    }

    public function handleMoveFile($actualdir = "", $targetdir = "", $filename = "")
    {
        parent::getParent()->handleMoveFile($actualdir = "", $targetdir = "", $filename = "");
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . "/Treeview.latte");
        $template->setTranslator($this->context->translator);
        $template->treeview = $this->loadData();
        $template->render();
    }

    private function generateTree($dirs, $superior)
    {
        $html = "<ul>";
        foreach ($dirs as $key => $value) {

            $html .= "<li>";
            $html .= '<span class="folder fm-droppable" data-targetdir="' . $superior . '/' . $key . '/' . '">';
            $html .= '<a href="' . parent::getParent()->link("showContent", "$superior/$key/") . '" class="treeview-folder fm-ajax" title="' . $this->context->filesystem->getRootName() . $superior . '/' . $key . '/">';
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

    /**
     *  @serializationVersion 1
     */
    private function generateTreeview()
    {
        $dirs = $this->getDirTree($this->context->parameters["uploadroot"] . $this->context->parameters["uploadpath"]);

        $rootname = $this->context->filesystem->getRootName();

        $output = '<ul class="filetree">';
        $output .= '<span class="fm-droppable folder-root" data-targetdir="' . $rootname . '">';
        $output .= '<a href="' . parent::getParent()->link("showContent", $rootname) . '" class="fm-ajax treeview-folder" title="' . $rootname . '">';
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
        if ($this->context->parameters["cache"]) {

            $path = $this->context->filesystem->getRealPath($this->context->parameters["uploadroot"] . $this->context->parameters["uploadpath"]);

            $caching = parent::getParent()->context->caching;
            $cacheData = $caching->getItem($path);

            if (!$cacheData) {

                $output = $this->generateTreeview();
                $caching->saveItem($path, $output, array("tags" => array("treeview")));
                return $output;
            } else {
                return $cacheData;
            }
        } else {
            return $this->generateTreeview();
        }
    }

}