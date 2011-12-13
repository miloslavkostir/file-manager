<?php

namespace Netfileman;

use Nette\Utils\Finder,
        Nette\Diagnostics\Debugger;

class Files extends FileManager
{
    /** @var array */
    public $config;

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
     * Copy file or folder from disk
     * @param  string  actual dir (relative path)
     * @param  string  target dir (relative path)
     * @param  string  filename
     * @return bool
     */
    function copy($actualdir, $targetdir, $filename)
    {
        $actualpath = $this['tools']->getAbsolutePath($actualdir);
        $targetpath = $this['tools']->getAbsolutePath($targetdir);
       
        if (is_writable($targetpath)) {

            $disksize = $this['tools']->diskSizeInfo();

            if ($this->config['cache'] == True)
                $this['caching']->deleteItem(array('content', $this['tools']->getRealPath($targetpath)));

            if (is_dir($actualpath . $filename)) {

                    if ($this->config['cache'] == True) {
                        $this['caching']->deleteItem(NULL, array('tags' => 'treeview'));
                        $this['caching']->deleteItem(array('content', $this['tools']->getRealPath($targetpath)));
                    }

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
                    $filesize = $this->getFileSize($actualpath . $filename);
                    if ($disksize['spaceleft'] >= $filesize) {
                            $this->copyFile($actualpath . $filename, $targetpath . $this->checkDuplName($targetpath, $filename));
                            return true;
                    } else
                            return false;
            }

        } else
            return false;
    }

    /**
     * Copy file (chunked)
     * @param string $src (absolute path)
     * @param string $dest (absolute path)
     * @return integer bytes written
     */
    function copyFile($src, $dest)
    {
        $buffer_size = 1048576;
        $ret = 0;
        $fin = fopen($src, "rb");
        $fout = fopen($dest, "w");

        while(!feof($fin)) {
            $ret += fwrite($fout, fread($fin, $buffer_size));
        }

        fclose($fin);
        fclose($fout);

        return $ret;
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

            while (false !== ( $file = readdir($dir)) ) {
                if ( ( $file != '.' ) && ( $file != '..' ) ) {

                        if ( is_dir($src . '/' . $file) ) {
                                if (strpos( '11' . $file, parent::getParent()->thumb) != 2 )  // exclude thumb folders
                                    $this->copyFolder($src . '/' . $file, $dst . '/' . $file);
                        } else
                                $this->copyFile($src . '/' . $file, $dst . '/' . $file);

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
            if ($folder->getRealPath() == $this['tools']->getRealPath($targetpath) )
                    $state = true;
                        
        }
        return $state;
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
        $actualpath = $this['tools']->getAbsolutePath($actualdir);
        $targetpath = $this['tools']->getAbsolutePath($targetdir);

        if ($actualdir == $targetdir)
                return false;
        else {
                if (is_dir($actualpath . $filename)) {
                        if ($this->isSubFolder($actualpath, $targetpath, $filename))
                                return false;
                        elseif ($this->moveFolder($actualpath, $targetpath, $filename)) {

                                if ($this->config['cache'] == True) {
                                    $this['caching']->deleteItemsRecursive($actualpath . $filename);
                                    $this['caching']->deleteItem(array('content', $this['tools']->getRealPath($actualpath)));
                                    $this['caching']->deleteItem(array('content', $this['tools']->getRealPath($targetpath)));
                                }

                                if ($this->deleteFolder($actualpath . $filename))
                                        return true;
                                else
                                        return false;

                        } else
                                return false;
                } else {
                        if ($this->moveFile($actualpath, $targetpath, $filename)) {

                                if ($this->config['cache'] == True) {
                                    $this['caching']->deleteItem(array('content', $this['tools']->getRealPath($actualpath)));
                                    $this['caching']->deleteItem(array('content', $this['tools']->getRealPath($targetpath)));
                                }

                                return true;
                        } else
                                return false;
                }
        }
    }

    /**
     * Move file
     * @param  string  actual folder (absolute path)
     * @param  string  target folder (absolute path)
     * @param  string  filename
     * @return bool
     */    
    function moveFile($actualpath, $targetpath, $filename)
    {
        if (rename($actualpath . $filename, $targetpath . $this->checkDuplName($targetpath, $filename))) {
            $this['clipboard']->clearClipboard();
            return true;
        } else
            return false;
    }
    
    /**
     * Move folder
     * @param  string  from (absolute path)
     * @param  string  to (absolute path)
     * @param  string  what
     * @return bool
     */
    function moveFolder($actualPath, $targetPath, $filename)
    {
        if(!is_dir($targetPath . $filename)) {
            $oldumask = umask(0);
            mkdir($targetPath . $filename, 0777);
            umask($oldumask);
        }

        $files = Finder::find('*')
                    ->in($actualPath . $filename)
                    ->exclude(parent::getParent()->thumb . '*');

        foreach ($files as $file) {

            if ($file->isDir())
                $this->moveFolder($file->getPath() . '/', $targetPath . $filename . '/', $file->getFilename());
            elseif (!rename($file->getPathName(), $targetPath . $filename . '/' . $file->getFileName()))
                return false;
        }

        return true;
    }

    /**
     * Get permissions
     * @param string $path
     * @return string
     */
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

    /**
     * Get file details
     * @param string $actualdir
     * @param string $filename
     * @return array
     */
    function fileDetails($actualdir, $filename)
    {
            $thumb_dir = $this->config['resource_dir'] . 'img/icons/';
            $uploadpath = $this->config['uploadpath'];
            $rootname = $this['tools']->getRootName();
            $uploadroot = $this->config['uploadroot'];

            $path = $this['tools']->getAbsolutePath($actualdir) . $filename;

            $info = array();

            if (!is_dir($path)) {

                $info['path'] = $path;
                $info['actualdir'] = $actualdir;
                $info['filename'] = $filename;
                $info['type'] = pathinfo($path, PATHINFO_EXTENSION);
                $info['size'] = $this->getFileSize($path);
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

    /**
     * Get info about files
     * @param string $dir
     * @param array $files
     * @param bool $iterate
     * @return array
     */
    function getFilesInfo($dir, $files, $iterate = false)
    {
        $path = $this['tools']->getAbsolutePath($dir);
        $info = array(
            'size' => 0,
            'dirCount' => 0,
            'fileCount' => 0            
        );
        
        foreach ($files as $file) {
            $filepath = $path . $file;
            if (!is_dir($filepath)) {
                    $info['size'] += $this->getFileSize($filepath);
                    $info['fileCount']++;
            } elseif ($iterate === true) {
                    $info['dirCount']++;                    
                    foreach (Finder::find('*')->from($filepath)->exclude(parent::getParent()->thumb . '*') as $item) {
                                       $info['size'] += $this['files']->getFileSize($item->getPathName());
                                       if ($item->isDir())
                                           $info['dirCount']++;
                                       else
                                           $info['fileCount']++;
                    }        
            }
        }
        
        return $info;
    }

    /**
     * Get file size for files > 2 GB
     * @param string
     * @return integer | false
     */
    function getFileSize($path)
    {
        $filesize = new Files\Filesize;

        $return = $filesize->sizeCurl($path);
        if ($return)
                return $return;

        $return = $filesize->sizeNativeSeek($path);
        if ($return)
                return $return;

        $return = $filesize->sizeCom($path);
        if ($return)
                return $return;

        $return = $filesize->sizeExec($path);
        if ($return)
                return $return;

        $return = $filesize->sizeNativeRead($path);
        if ($return)
                return $return;

        Debugger::log("File size error at file $path.", Debugger::WARNING);
        return false;
    }

    function getFolderInfo($path)
    {
        $info = array();
        $info['size'] = 0;
        $info['count'] = 0;
        foreach (Finder::findFiles('*')->from($path)->exclude(parent::getParent()->thumb . '*') as $file) {
                           $info['size'] += $this['files']->getFileSize($file->getPathName());
                           $info['count']++;
        }
        return $info;
    }

    /**
     * Remove file name diacritic
     * @param string $string
     * @return string
     */
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

    /**
     * Get safe folder name
     * @param string $name
     * @return string
     */
    function safe_foldername($name) {
        $except = array('\\', '/', ':', '*', '?', '"', '<', '>', '|');
        $name = str_replace($except, '', $name);

        if (substr($name, 0) == ".")
                $name = "";
        elseif (str_replace(array('.', ' '), '', $name) == "")  # because of this: .. .
                $name = "";

        return $this->remove_diacritic($name);
    }

    /**
     * Get safe file name
     * @param string $name
     * @return string
     */
    function safe_filename($name)
    {
        $except = array('\\', '/', ':', '*', '?', '"', '<', '>', '|');
        $name = str_replace($except, '', $name);

        return $this->remove_diacritic($name);
    }

    /**
     * Delete file or folder from disk
     * @param  string  folder (relative path)
     * @param  string  filename - optional
     * @return bool
     */
    function delete($dir, $file = "")
    {
        $absDir = $this['tools']->getAbsolutePath($dir);

        if (is_dir($absDir . $file)) {

                if ($this->config['cache'] == True)
                    $this['caching']->deleteItemsRecursive($absDir);
                
                if ($this->deleteFolder($absDir . $file)) {

                    return true;
                } else
                    return false;

        } else {
                if ($this->deleteFile($dir, $file))
                    return true;
                else
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
        $path = $this['tools']->getAbsolutePath($actualdir);

        if (is_writable($path)) {
                $cache_file =  $this->createThumbName($actualdir, $filename);
                // delete thumb
                if ( file_exists($cache_file['path']) && is_writable($path . $filename) )
                   unlink($cache_file['path']);

                // delete source file
                if (unlink($path . $filename)) {
                    if ($this->config['cache'] == True)
                        $this['caching']->deleteItem(array('content', $this['tools']->getRealPath($path)));
                    return true;
                } else
                    return false;
                
        } else
                return false;
    }    

    /**
     * Delete folder recursively
     * TODO use Finder
     * thx O S http://php.net/manual/en/function.rmdir.php
     * @param string aboslute path
     * @param-optional bool only clear direcotry content if true
     * @return bool
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
                if(!rmdir($directory))
                    return false;
            }

            return true;
        }
    }

    /**
     * Get name for thumb file & create thumb folder if not exists
     * @param string $actualdir
     * @param string $filename
     * @return array
     */
    function createThumbName($actualdir, $filename)
    {
        $result = array();
        $uploadpath = $this->config['uploadpath'];
        $rootname = $this['tools']->getRootName();
        $uploadroot = $this->config['uploadroot'];

        $thumb_folder = $this->createThumbFolder($actualdir);

        if ($actualdir == $rootname) {
            $path = $uploadroot . $uploadpath . $filename;
            $result['name'] = parent::getParent()->thumb . md5($filename . $this->getFileSize($path)) . "." . pathinfo($filename, PATHINFO_EXTENSION);
            $result['path'] = $uploadroot . $uploadpath . $thumb_folder . "/" . $result['name'];
        } else {
            $path = $uploadroot . substr($uploadpath,0,-1) . $actualdir . $filename;            
            $result['name'] = parent::getParent()->thumb . md5($filename . $this->getFileSize($path)) . "." . pathinfo($filename, PATHINFO_EXTENSION);
            $result['path'] = $uploadroot . substr($uploadpath,0,-1) . $actualdir . $thumb_folder . "/" . $result['name'];
        }
        
        return $result;
    }

    /**
     * Create thumb folder if not exists
     * @param string $actualdir
     * @return string
     */
    function createThumbFolder($actualdir)
    {
        $uploadpath = $this->config['uploadpath'];
        $rootname = $this['tools']->getRootName();
        $uploadroot = $this->config['uploadroot'];

        $foldername = parent::getParent()->thumb . md5($actualdir);

        if ($actualdir == $rootname)
            $path = $uploadpath;
        else
            $path = substr($uploadpath, 0, -1) . $actualdir;

        if (!is_dir($uploadroot . $path . $foldername)) {
            $oldumask = umask(0);
            mkdir($uploadroot . $path . $foldername, 0777);
            umask($oldumask);
        }

        return $foldername;
    }
}