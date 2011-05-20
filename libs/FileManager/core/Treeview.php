<?php

use Nette\Caching\Cache;
use Nette\Caching\Storages\FileStorage;

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

   function getDirTree($dir, $showfiles=false, $iterateSubDirectories=true )
   {
            $d = dir($dir);
            $x = array();

            while (false !== ($r = $d->read())) {

                    if($this['files']->isThumbDir($r) != True && $r != "." && $r != ".." && ((!preg_match('/^\..*/', $r) && !is_dir($dir.$r)) || is_dir($dir.$r)) && (($showfiles == false && is_dir($dir.$r)) || $showfiles == true)) {
                            $x[$r] = (is_dir($dir.$r)?array():(is_file($dir.$r)?true:false));
                    }
            }
            foreach ($x as $key => $value) {
                            if (is_dir($dir.$key."/") && $iterateSubDirectories) {
                                    $x[$key] = $this->getDirTree($dir.$key."/", $showfiles);
                            } else {
                                    $x[$key] = is_file($dir.$key) ? (preg_match("/\.([^\.]+)$/", $key, $matches) ? str_replace(".","",$matches[0]) : 'file') : "folder";
                            }
            }
            uksort($x, "strnatcasecmp");
            return $x;
    }

    function generateTreeview()
    {
        $dirs = $this->getDirTree($this->config['uploadroot'] . $this->config['uploadpath'], false);
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