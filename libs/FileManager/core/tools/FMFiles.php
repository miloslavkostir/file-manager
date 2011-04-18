<?php

use Nette\Utils\Finder;

class FMFiles extends FileManager
{
    /** @var array */
    public $config;

    /**
     * @var string
     * Prefix for thumb folders and thumbnails
     */
    public $thumb;

    public function __construct()
    {
        parent::__construct();
    }

    function isThumb($path)
    {
        $checkname = substr_count(strtolower($path), $this->thumb);
        $checkname_pos = strpos(strtolower($path), $this->thumb);
        if ( ($checkname > 0) and ($checkname_pos == 0))
            return True;
        else
            return False;
    }

    function isThumbDir($name)
    {
        $checkname = substr_count(strtolower($name), $this->thumb);
        $checkname_pos = strpos(strtolower($name), $this->thumb);
        if ( ($checkname > 0) and ($checkname_pos == 0))
            return True;
        else
            return False;
    }

    function get_file_mod($path)
    {
        $perms = fileperms($path);

        if (($perms & 0xC000) == 0xC000) {
            // Socket
            $info = 's';
        } elseif (($perms & 0xA000) == 0xA000) {
            // Symbolic Link
            $info = 'l';
        } elseif (($perms & 0x8000) == 0x8000) {
            // Regular
            $info = '-';
        } elseif (($perms & 0x6000) == 0x6000) {
            // Block special
            $info = 'b';
        } elseif (($perms & 0x4000) == 0x4000) {
            // Directory
            $info = 'd';
        } elseif (($perms & 0x2000) == 0x2000) {
            // Character special
            $info = 'c';
        } elseif (($perms & 0x1000) == 0x1000) {
            // FIFO pipe
            $info = 'p';
        } else {
            // Unknown
            $info = 'u';
        }

        // Owner
        $info .= (($perms & 0x0100) ? 'r' : '-');
        $info .= (($perms & 0x0080) ? 'w' : '-');
        $info .= (($perms & 0x0040) ?
                    (($perms & 0x0800) ? 's' : 'x' ) :
                    (($perms & 0x0800) ? 'S' : '-'));

        // Group
        $info .= (($perms & 0x0020) ? 'r' : '-');
        $info .= (($perms & 0x0010) ? 'w' : '-');
        $info .= (($perms & 0x0008) ?
                    (($perms & 0x0400) ? 's' : 'x' ) :
                    (($perms & 0x0400) ? 'S' : '-'));

        // World
        $info .= (($perms & 0x0004) ? 'r' : '-');
        $info .= (($perms & 0x0002) ? 'w' : '-');
        $info .= (($perms & 0x0001) ?
                    (($perms & 0x0200) ? 't' : 'x' ) :
                    (($perms & 0x0200) ? 'T' : '-'));

        return $info;
    }

    function fileDetails($actualdir, $filename)
    {
            $thumb_dir = $this->config['resource_dir'] . 'img/icons/';
            $uploadpath = $this->config['uploadpath'];
            $rootname = $this->getRootName();
            $uploadroot = $this->config['uploadroot'];

            $path = parent::getParent()->getAbsolutePath($actualdir) . $filename;

            $info = array();

            if (!is_dir($path)) {

                $info['path'] = $path;
                $info['actualdir'] = $actualdir;
                $info['filename'] = $filename;
                $info['type'] = pathinfo($path, PATHINFO_EXTENSION);
                $info['size'] = filesize($path);
                $info['modificated'] = date("F d Y H:i:s", filemtime($path));
                $info['permissions'] = $this->get_file_mod($path);
                
                if (file_exists(WWW_DIR . $thumb_dir . "large/" .strtolower($info['type']) . ".png"))
                    $info["icon"] =  $thumb_dir . "large/" . $info['type'] . ".png";
                else 
                    $info["icon"] =  $thumb_dir . "large/icon.png";
                
            } else {
                $folder_info = $this->getFolderInfo($path);
                $info['path'] = $path;
                $info['actualdir'] = $actualdir;
                $info['filename'] = $filename;
                $info['type'] = 'folder';
                $info['size'] = $folder_info['size'];
                $info['files_count'] = $folder_info['count'];
                $info['modificated'] = date("F d Y H:i:s", filemtime($path));
                $info['permissions'] = $this->get_file_mod($path);
                $info["icon"] =  $thumb_dir . "large/folder.png";
            }
            
            return $info;
    }

    public function getFolderInfo($path)
    {
        $info = array();
        $info['size'] = 0;
        $info['count'] = 0;
        foreach (Finder::findFiles('*')->from($path)->exclude($this->thumb . '*') as $file) {
                           $info['size'] += $file->getSize();
                           $info['count']++;
        }
        return $info;
    }

    function remove_diacritic($string)
    {
        $charset = Array(
          'ä'=>'a',
          'Ä'=>'A',
          'á'=>'a',
          'Á'=>'A',
          'à'=>'a',
          'À'=>'A',
          'ã'=>'a',
          'Ã'=>'A',
          'â'=>'a',
          'Â'=>'A',
          'č'=>'c',
          'Č'=>'C',
          'ć'=>'c',
          'Ć'=>'C',
          'ď'=>'d',
          'Ď'=>'D',
          'ě'=>'e',
          'Ě'=>'E',
          'é'=>'e',
          'É'=>'E',
          'ë'=>'e',
          'Ë'=>'E',
          'è'=>'e',
          'È'=>'E',
          'ê'=>'e',
          'Ê'=>'E',
          'í'=>'i',
          'Í'=>'I',
          'ï'=>'i',
          'Ï'=>'I',
          'ì'=>'i',
          'Ì'=>'I',
          'î'=>'i',
          'Î'=>'I',
          'ľ'=>'l',
          'Ľ'=>'L',
          'ĺ'=>'l',
          'Ĺ'=>'L',
          'ń'=>'n',
          'Ń'=>'N',
          'ň'=>'n',
          'Ň'=>'N',
          'ñ'=>'n',
          'Ñ'=>'N',
          'ó'=>'o',
          'Ó'=>'O',
          'ö'=>'o',
          'Ö'=>'O',
          'ô'=>'o',
          'Ô'=>'O',
          'ò'=>'o',
          'Ò'=>'O',
          'õ'=>'o',
          'Õ'=>'O',
          'ő'=>'o',
          'Ő'=>'O',
          'ř'=>'r',
          'Ř'=>'R',
          'ŕ'=>'r',
          'Ŕ'=>'R',
          'š'=>'s',
          'Š'=>'S',
          'ś'=>'s',
          'Ś'=>'S',
          'ť'=>'t',
          'Ť'=>'T',
          'ú'=>'u',
          'Ú'=>'U',
          'ů'=>'u',
          'Ů'=>'U',
          'ü'=>'u',
          'Ü'=>'U',
          'ù'=>'u',
          'Ù'=>'U',
          'ũ'=>'u',
          'Ũ'=>'U',
          'û'=>'u',
          'Û'=>'U',
          'ý'=>'y',
          'Ý'=>'Y',
          'ž'=>'z',
          'Ž'=>'Z',
          'ź'=>'z',
          'Ź'=>'Z'
        );

        return strtr($string, $charset);
    }

    function safe_foldername($name) {
        $except = array('\\', '/', ':', '*', '?', '"', '<', '>', '|');
        $name = str_replace($except, '', $name);

        if (substr($name, 0) == ".")    // folder name can not start with .
                $name = "";
        elseif (str_replace(array('.', ' '), '', $name) == "")  // because of this: .. .
                $name = "";

        return $this->remove_diacritic($name);
    }

    function safe_filename($name)
    {
        $except = array('\\', '/', ':', '*', '?', '"', '<', '>', '|');
        $name = str_replace($except, '', $name);

        return $this->remove_diacritic($name);
    }

    // http://php.net/manual/en/function.rmdir.php
    function deleteFolder($directory, $empty = false)
    {
        if(substr($directory,-1) == "/") {
            $directory = substr($directory,0,-1);
        }

        if(!file_exists($directory) || !is_dir($directory)) {
            return false;
        } elseif(!is_readable($directory)) {
            return false;
        } else {
            $directoryHandle = opendir($directory);

            while ($contents = readdir($directoryHandle)) {
                if($contents != '.' && $contents != '..') {
                    $path = $directory . "/" . $contents;

                    if(is_dir($path)) {
                        $this->deleteFolder($path);
                    } else {
                        if ( is_writable($path))
                            unlink($path);
                        else
                            return false;
                    }
                }
            }

            closedir($directoryHandle);

            if($empty == false) {
                if(!@rmdir($directory)) {
                    return false;
                }
            }

            return true;
        }
    }

    function createThumbName($actualdir, $filename)
    {
        $result = array();
        $uploadpath = $this->config['uploadpath'];
        $rootname = parent::getParent()->getRootName();
        $uploadroot = $this->config['uploadroot'];

        $thumb_folder = $this->createThumbFolder($actualdir);

        if ($actualdir == $rootname) {
            $path = $uploadroot . $uploadpath . $filename;
            $result['name'] = $this->thumb .md5($filename . @filesize($path)) . "." . pathinfo($filename, PATHINFO_EXTENSION);
            $result['path'] = $uploadroot . $uploadpath . $thumb_folder . "/" . $result['name'];
        } else {
            $path = $uploadroot . substr($uploadpath,0,-1) . $actualdir . $filename;            
            $result['name'] = $this->thumb .md5($filename . @filesize($path)) . "." . pathinfo($filename, PATHINFO_EXTENSION);
            $result['path'] = $uploadroot . substr($uploadpath,0,-1) . $actualdir . $thumb_folder . "/" . $result['name'];
        }
        
        return $result;
    }

    function createThumbFolder($actualdir)
    {
        $uploadpath = $this->config['uploadpath'];
        $rootname = parent::getParent()->getRootName();
        $uploadroot = $this->config['uploadroot'];
        
        $foldername = $this->thumb . md5($actualdir);
        
        if ($actualdir == $rootname)
            $path = $uploadpath ;
        else
            $path = substr($uploadpath, 0, -1) . $actualdir;
        
        if (!@is_dir($uploadroot . $path . $foldername)) {
            $oldumask = umask(0);
            mkdir($uploadroot . $path . $foldername, 0777);
            umask($oldumask);
        }
        
        return $foldername;
    }

    function smartCopy($source, $dest, $options = array('folderPermission'=>0777, 'filePermission'=>0755))
    {
        $result = false;

        if (is_file($source)) {
            if ($dest[strlen($dest)-1] == '/') {
                if (!file_exists($dest)) {
                    cmfcDirectory::makeAll($dest, $options['folderPermission'], true);
                }
                $__dest = $dest . "/" . basename($source);
            } else {
                $__dest = $dest;
            }
            $result = copy($source, $__dest);
            chmod($__dest, $options['filePermission']);

        } elseif(is_dir($source)) {
            if ($dest[strlen($dest)-1] == '/') {
                if ($source[strlen($source)-1]=='/') {
                    //Copy only contents
                } else {
                    //Change parent itself and its contents
                    $dest = $dest . basename($source);
                    @mkdir($dest);
                    chmod($dest, $options['filePermission']);
                }
            } else {
                if ($source[strlen($source)-1]=='/') {
                    //Copy parent directory with new name and all its content
                    @mkdir($dest, $options['folderPermission']);
                    chmod($dest, $options['filePermission']);
                } else {
                    //Copy parent directory with new name and all its content
                    @mkdir($dest, $options['folderPermission']);
                    chmod($dest, $options['filePermission']);
                }
            }

            $dirHandle = opendir($source);
            while($file = readdir($dirHandle))
            {
                if($file != "." && $file != "..")
                {
                    if(!is_dir($source . "/" . $file)) {
                        $__dest = $dest . "/" . $file;
                    } else {
                        $__dest = $dest . "/" . $file;
                    }
                    $result = $this->smartCopy($source . "/" . $file, $__dest, $options);
                }
            }
            closedir($dirHandle);

        } else {
            $result = false;
        }
        return $result;
    }


    function bytes_from_string($val)
    {
        $last = strtolower(substr($val, strlen($val)-2, 2));
	$vowels = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "0", " ");
        $last = str_replace($vowels, '', $last);

        // 'gb' and 'g' modifier are available since PHP 5.1.0
        if ($last == 'gb' || $last == 'g' )
                $val *= 1024*1024*1024;
        if ($last == 'mb' || $last == 'm' )
                $val *= 1024*1024;
        if ($last == 'kb' || $last == 'k' )
                $val *= 1024;
        if ($last == 'b')
                $val *= 1;

        return $val;
    }


    function recurse_copy($src, $dst)
    {
            $dir = opendir($src);
            $oldumask = umask(0);
            @mkdir($dst);
            umask($oldumask);

            while(false !== ( $file = readdir($dir)) ) {                
                if ( ( $file != '.' ) && ( $file != '..' ) ) {

                        if ( is_dir($src . '/' . $file) ) {
                                if (strpos( '11' . $file, $this->thumb) != 2 )  // exclude thumb folders
                                    $this->recurse_copy($src . '/' . $file, $dst . '/' . $file);
                        } else
                                copy($src . '/' . $file, $dst . '/' . $file);

                }
            }

            closedir($dir);
    }
}