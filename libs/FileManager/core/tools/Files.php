<?php

use Nette\Utils\Finder;

class Files extends FileManager
{
    /** @var array */
    public $config;

    /**
     * Prefix for thumb folders and thumbnails
     * @var string
     */
    public $thumb;

    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Check if file exists and give alternative name
     * @param  string  actual dir (absolute path)
     * @param  string  filename
     * @return string
     */    
    public function checkDuplName($targetpath, $filename)
    {   
        if (file_exists($targetpath . $filename)) {
            $i = 1;
            while (file_exists($targetpath . '(' . $i . ')' . $filename)) {
                $i++;
            }
            return '(' . $i . ')' . $filename;
        } else
            return $filename;        
    }    
    
    /**
     * Copy file or folder from disk and clear cache
     * @param  string  actual dir (relative path)
     * @param  string  target dir (relative path)
     * @param  string  filename
     * @return bool
     */
    function copy($actualdir, $targetdir, $filename)
    {
        $actualpath = parent::getParent()->getAbsolutePath($actualdir);
        $targetpath = parent::getParent()->getAbsolutePath($targetdir);
       
        if (is_writable($targetpath)) {

            $disksize = $this['tools']->diskSizeInfo();            
            
            if (is_dir($actualpath . $filename)) {
                    $dirinfo = $this->getFolderInfo(realpath($actualpath . $filename));
                    if ($disksize['spaceleft'] < $dirinfo['size'])
                                    return false;
                    else {                        
                                    if ($this->isSubFolder($actualpath, $targetpath, $filename) == false) {                                        
                                        $this->copyFolder($actualpath . $filename, $targetpath . $this->checkDuplName($targetpath, $filename));
                                        return true;
                                    } else
                                        return false;
                    }                
            } else {
                    $filesize = filesize($actualpath . $filename);
                    if ($disksize['spaceleft'] >= $filesize) {
                            copy($actualpath . $filename, $targetpath . $this->checkDuplName($targetpath, $filename));
                            return true;
                    } else
                            return false;
            }

        } else
            return false;
    }

    /**
     * Copy folder recursively
     * @param  string  actual dir (absolute path)
     * @param  string  target dir (absolute path)
     */    
    function copyFolder($src, $dst)
    {
            $dir = opendir($src);
            $oldumask = umask(0);
            mkdir($dst);
            umask($oldumask);

            while(false !== ( $file = readdir($dir)) ) {                
                if ( ( $file != '.' ) && ( $file != '..' ) ) {

                        if ( is_dir($src . '/' . $file) ) {
                                if (strpos( '11' . $file, $this->thumb) != 2 )  // exclude thumb folders
                                    $this->copyFolder($src . '/' . $file, $dst . '/' . $file);
                        } else
                                copy($src . '/' . $file, $dst . '/' . $file);

                }
            }

            closedir($dir);
    }

    /**
     * Check if target folder is it's sub-folder
     * @param  string  actual dir (absolute path)
     * @param  string  target dir (absolute path)
     * @param  string  filename
     * @return bool
     */    
    function isSubFolder($actualpath, $targetpath, $filename)
    {
        $state = false;
        
        foreach (Finder::findDirectories('*')->from(realpath($actualpath . $filename)) as $folder) {          
            if ($folder->getRealPath() == realpath($targetpath) )
                    $state = true;
                        
        }
        return $state;
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
    
    /**
     * Move file or folder
     * @param  string  actual folder (relative path)
     * @param  string  target folder (relative path)
     * @param  string  filename
     * @return bool
     */    
    public function move($actualdir, $targetdir, $filename)
    {
        $actualpath = parent::getParent()->getAbsolutePath($actualdir);

        if ($actualdir == $targetdir)
                return false;
        else {
                if (is_dir($actualpath . $filename)) {
                        if ($this->moveFolder($actualdir, $targetdir, $filename))
                                return true;
                        else
                                return false;
                } else {
                        if ($this->moveFile($actualdir, $targetdir, $filename))
                                return true;
                        else
                                return false;
                }        
        }
    }
    
    /**
     * Move file
     * @param  string  actual folder (relative path)
     * @param  string  target folder (relative path)
     * @param  string  filename
     * @return bool
     */    
    public function moveFile($actualdir, $targetdir, $filename)
    {
        $actualpath = parent::getParent()->getAbsolutePath($actualdir);
        $targetpath = parent::getParent()->getAbsolutePath($targetdir);
        
        if (copy($actualpath . $filename, $targetpath . $this->checkDuplName($targetpath, $filename))) {
            $this->deleteFile($actualdir, $filename);
            $this['clipboard']->clearClipboard();

            // refresh folder content cache
            $this['tools']->clearFromCache(array('fmfiles', $actualdir));
            $this['tools']->clearFromCache(array('fmfiles', $targetdir));                    
            
            return true;
        } else
            return false;
    }
    
    /**
     * Move folder
     * @param  string  actual folder (relative path)
     * @param  string  target folder (relative path)
     * @param  string  filename
     * @return bool
     */    
    public function moveFolder($actualdir, $targetdir, $filename)
    {
        $actualpath = parent::getParent()->getAbsolutePath($actualdir);
        
        if ($this->copy($actualdir, $targetdir, $filename)) {
            if ($this->delete($actualdir, $filename))
                return true;
            else
                return false;
        } else
            return false;
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
    
    /*
     * $files   @array
     * $dir     @string - absolutepath
     * $iterate @bool   - include subdirectories
     * @return array
     */
    public function getFilesInfo($dir, $files, $iterate = false)
    {
        $path = parent::getParent()->getAbsolutePath($dir);
        $info = array(
            'size' => 0,
            'dirCount' => 0,
            'fileCount' => 0            
        );
        
        foreach ($files as $file) {
            $filepath = $path . $file;
            if (!is_dir($filepath)) {
                    $info['size'] += filesize($filepath);
                    $info['fileCount']++;
            } elseif ($iterate === true) {
                    $info['dirCount']++;                    
                    foreach (Finder::find('*')->from($filepath)->exclude($this->thumb . '*') as $item) {
                                       $info['size'] += $item->getSize();
                                       if ($item->isDir())
                                           $info['dirCount']++;
                                       else
                                           $info['fileCount']++;
                    }        
            }
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

    /**
     * Delete file or folder from disk and clear cache
     * @param  string  folder (relative path)
     * @param  string  filename - optional
     * @return bool
     */
    public function delete($dir, $file = "")
    {
        $absDir = parent::getParent()->getAbsolutePath($dir);
        
        if (is_dir($absDir . $file)) {
                     
                if ($this->deleteFolder($absDir . $file)) {
                    
                    // clear actual dir cache
                    $this['tools']->clearFromCache(array('fmfiles', $dir));           
                    
                    // clear sub-folders cache recursively
                    if ($dir == parent::getParent()->getRootname())
                        $startDir = null;
                    else
                        $startDir = substr($dir, 0, -1);                      
                    $dirs = $this['treeview']->getDirTree($absDir);
                    $this['tools']->clearDirCache($dirs, $startDir);
                    
                    // clear treeview cache
                    $this['tools']->clearFromCache('fmtreeview');
                    
                    return true;
                } else
                    return false;
                
        } else {
                if ($this->deleteFile($dir, $file)) {
                    $this['tools']->clearFromCache(array('fmfiles', $dir));                    
                    return true;
                } else
                    return false;
        }
    }
    
    /**
     * Delete file
     * @param  string  relative folder path
     * @param  string  filename
     * @return bool
     */
    function deleteFile($actualdir, $filename)
    {
        $path = parent::getParent()->getAbsolutePath($actualdir);

        if (is_writable($path)) {
                $cache_file =  $this->createThumbName($actualdir, $filename);
                // delete thumb
                if ( file_exists($cache_file['path']) && is_writable($path . $filename) )
                   unlink($cache_file['path']);

                // delete source file
                if (unlink($path . $filename))                   
                    return true;                    
                else
                    return false;
                
        } else
                return false;
    }    
    
    /**
     * Delete folder recursively
     * @param string aboslute path
     * @param-optional bool only clear direcotry content if true
     * @return bool
     * thx O S http://php.net/manual/en/function.rmdir.php
     * 
     */
    function deleteFolder($directory, $empty = false)
    {
        if(substr($directory,-1) == "/")
            $directory = substr($directory,0,-1);

        if(!file_exists($directory) || !is_dir($directory))
            return false;
        elseif(!is_readable($directory))
            return false;
        else {
            $directoryHandle = opendir($directory);

            while ($contents = readdir($directoryHandle)) {
                if($contents != '.' && $contents != '..') {
                    $path = $directory . "/" . $contents;

                    if(is_dir($path))
                        $this->deleteFolder($path);
                    else {
                        if ( is_writable($path))
                            unlink($path);
                        else
                            return false;
                    }
                }
            }

            closedir($directoryHandle);

            if($empty == false) {
                if(!@rmdir($directory))
                    return false;
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
}