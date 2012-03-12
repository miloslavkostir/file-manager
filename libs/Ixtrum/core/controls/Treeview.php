<?php

namespace Ixtrum;

use Nette\Utils\Finder,
        Nette\DI\Container;


class Treeview extends FileManager
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
                $template->treeview = $this->loadData();
                $template->render();
        }


        private function generateTree($dirs, $superior)
        {
                $html = "<ul>";
                foreach ($dirs as $key => $value) {

                        $html .= '<li>
                                    <span class="folder fm-droppable" id="'.$superior . '/' . $key.'/' . '">
                                        <a href="' . parent::getParent()->link("showContent", $superior . '/' . $key. '/') . '" class="treeview-folder fm-ajax" title="' . $this->context->tools->getRootName() . $superior. '/' . $key . '/">'
                                            . $key . '
                                        </a>
                                    </span>';

                        if (count($dirs[$key]) > 0) {
                                $sub_superior = $superior . "/" . $key;
                                $html .= $this->generateTree($dirs[$key], $sub_superior);
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
         *
         *  @serializationVersion 1
         */
        private function generateTreeview()
        {
                $dirs = $this->getDirTree($this->context->parameters["uploadroot"] . $this->context->parameters["uploadpath"]);

                $rootname = $this->context->tools->getRootName();

                return '<ul class="filetree" style="display: block;">
                            <span class="fm-droppable folder-root" id="' . $rootname . '">
                                <a href="' . parent::getParent()->link("showContent", $rootname ) . '" class="fm-ajax treeview-folder" title="' . $rootname . '">'
                                        . $rootname .
                                '</a>
                            </span>'.
                            $this->generateTree($dirs, null).
                        '</ul>';
        }


        /**
         * Load data
         *
         * @return string
         */
        public function loadData()
        {
                if ($this->context->parameters["cache"]) {

                        $path = $this->context->tools->getRealPath($this->context->parameters["uploadroot"] . $this->context->parameters["uploadpath"]);

                        $caching = parent::getParent()->context->caching;
                        $cacheData = $caching->getItem($path);

                        if (!$cacheData) {

                                $output = $this->generateTreeview();
                                $caching->saveItem($path, $output, array("tags" => array("treeview")));
                                return $output;
                        } else
                                return $cacheData;
                } else
                        return $this->generateTreeview();
        }
}