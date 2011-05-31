<?php

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;
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

        // set language
        $lang_file = __DIR__ . '/../locale/FileManager.'. $this->config['lang'].'.mo';
        if (file_exists($lang_file))
            $template->setTranslator(new GettextTranslator($lang_file));
        else
             throw new Exception ("Language file " . $lang_file . " doesn't exist! Application can not be loaded!");

        // rendering treeview with caching
        $cache_const = md5($this->config['uploadroot'] . $this->config['uploadpath']);
        $cache_dir = parent::getParent()->cache_path . $cache_const;
        $storage = new FileStorage($cache_dir);
        $cache = new Cache($storage);
        if (isset($cache['fmtreeview']))
            $template->treeview = $cache['fmtreeview'];
        else {
            $template->treeview = $this->generateTreeview();
            $cache->save('fmtreeview', $this->generateTreeview());
        }

        $template->config = $this->config;

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
}