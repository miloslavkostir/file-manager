<?php

use Nette\Utils\Finder;

class Treeview extends FileManager
{
    /** @var array */
    public $config;

    public function __construct()
    {
        parent::__construct();
    }

    public function handleMoveFile($actualdir = "", $targetdir = "", $filename = "")
    {
        parent::getParent()->handleMoveFile($actualdir = "", $targetdir = "", $filename = "");
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/Treeview.latte');
        $template->setTranslator(parent::getParent()->getTranslator());
        $template->treeview = $this->loadData();
        $template->render();
    }

    function generateTree($dirs, $superior)
    {
            $html = "<ul>";
            foreach ($dirs as $key => $value) {
                    $html .= '<li>
                                <span class="folder fm-droppable" id="'.$superior . '/' . $key.'/'.'">
                                    <a href="' . parent::getParent()->link('showContent', $superior . '/' . $key.'/').'" class="treeview-folder fm-ajax" title="'.parent::getParent()->getRootname().$superior.'/'.$key.'/">'
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

   function getDirTree($dir)
   {
       $x = array();
       $dirs = Finder::findDirectories('*')
                    ->in($dir)
                    ->exclude(parent::getParent()->thumb . '*');

       foreach ($dirs as $dir)
           $x[$dir->getFilename()] = $this->getDirTree($dir->getPathName());

       return $x;
    }

    function generateTreeview()
    {
        $dirs = $this->getDirTree($this->config['uploadroot'] . $this->config['uploadpath']);

        $rootname = parent::getParent()->getRootname();

        return '<ul class="filetree" style="display: block;">
                    <span class="fm-droppable folder-root" id="' . $rootname . '">
                        <a href="' . parent::getParent()->link('showContent', $rootname ).'" class="fm-ajax treeview-folder" title="' . $rootname . '">'
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
        if ($this->config['cache'] == True) {

            $path = $this['tools']->getRealPath($this->config['uploadroot'] . $this->config['uploadpath']);

            $cacheData = $this['caching']->getItem($path);

            if (empty($cacheData)) {
                $output = $this->generateTreeview();
                $this['caching']->saveItem($path, $output, array('tags' => array('treeview')));
                return $output;
            } else
                return $cacheData;

        } else
            return $this->generateTreeview();
    }
}