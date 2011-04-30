<?php

use Nette\Environment;
use Nette\Utils\Finder;

class FMUpload extends FileManager
{
    /** @var array */
    public $config;
   
    public function __construct()
    {
        parent::__construct();
        
        // upload limit detection
        if ($this->config['upload_chunk'] == True) {
                $post_max_size = $this['fmFiles']->bytes_from_string(ini_get('post_max_size'));
                $upload_chunk_size = $this['fmFiles']->bytes_from_string($this->config['upload_chunk_size']);
                $upload_max_filesize = $this['fmFiles']->bytes_from_string(ini_get('upload_max_filesize'));

                if ($post_max_size < $upload_chunk_size)
                    throw new Exception ("Upload chunk size option (" . $this->config['upload_chunk_size'] . ") is bigger than allowed POST_MAX_SIZE (" . ini_get('post_max_size') . ") in php.ini. Files can not be uploaded!");
                elseif ($upload_max_filesize < $upload_chunk_size)
                    throw new Exception ("Upload chunk size option (" . $this->config['upload_chunk_size'] . ") is bigger than allowed UPLOAD_MAX_FILESIZE (" . ini_get('upload_max_filesize') . ") in php.ini. Files can not be uploaded!");
        }
    }
   
    public function handleUpload($actualdir)
    {
	// HTTP headers for no cache etc
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");

        $translator = new GettextTranslator(__DIR__ . '/../locale/FileManager.' . $this->config["lang"] . '.mo');

        if ($this->config['readonly'] == True)
            die('{"jsonrpc" : "2.0", "result" : "' . $translator->translate("File manager is in read-only mode! Files can not be uploaded") . '", "type" : "warning"}');
        else {

                    $size = 0;
                    $filesize = filesize($_FILES["file"]["tmp_name"]);

                    foreach (Finder::findFiles('*')->from($this->config['uploadroot'] . $this->config['uploadpath']) as $file) {
                                       $size += $file->getSize();
                    }

                    if ($this->config['quota'] == True)
                        $freespace = ($this->config['quota_limit'] * 1048576) - $size;
                    else
                        $freespace = disk_free_space($this->config['uploadroot'] . $this->config['uploadpath']);

                    if ( $freespace < $filesize )
                            die('{"jsonrpc" : "2.0", "result" : "' . $translator->translate("Disk full! File was not uploaded completely!").'", "type" : "error"}');
                    else {
                            $this['tools']->clearFromCache(array('fmfiles', $actualdir));

                            $targetDir = parent::getParent()->getAbsolutePath($actualdir);

                            if ( file_exists($targetDir)) {

                                        // Settings
                                        $cleanupTargetDir = false; // Remove old files
                                        $maxFileAge = 60 * 60; // Temp file age in seconds

                                        // 5 minutes execution time
                                        @set_time_limit(5 * 60);

                                        // Uncomment this one to fake upload time
                                        // usleep(5000);

                                        // Get parameters
                                        $chunk = isset($_REQUEST["chunk"]) ? $_REQUEST["chunk"] : 0;
                                        $chunks = isset($_REQUEST["chunks"]) ? $_REQUEST["chunks"] : 0;
                                        $fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';

                                        // Clean the fileName for security reasons
                                        $fileName = $this['fmFiles']->safe_filename($fileName);

                                        // Make sure the fileName is unique but only if chunking is disabled
                                        if ($chunks < 2 && file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName)) {
                                                $ext = strrpos($fileName, '.');
                                                $fileName_a = substr($fileName, 0, $ext);
                                                $fileName_b = substr($fileName, $ext);

                                                $count = 1;
                                                while (file_exists($targetDir . DIRECTORY_SEPARATOR . $fileName_a . '_' . $count . $fileName_b))
                                                        $count++;

                                                $fileName = $fileName_a . '_' . $count . $fileName_b;
                                        }

                                        // Remove old temp files
                                        if (is_dir($targetDir) && ($dir = opendir($targetDir))) {
                                                while (($file = readdir($dir)) !== false) {
                                                        $filePath = $targetDir . DIRECTORY_SEPARATOR . $file;

                                                        // Remove temp files if they are older than the max age
                                                        if (preg_match('/\\.tmp$/', $file) && (filemtime($filePath) < time() - $maxFileAge))
                                                                @unlink($filePath);
                                                }

                                                closedir($dir);
                                        } else
                                                die('{"jsonrpc" : "2.0", "result" : "' . $translator->translate("Failed to open temp directory") . '", "id" : "id", "type" : "error"}');

                                        // Look for the content type header
                                        if (isset($_SERVER["HTTP_CONTENT_TYPE"]))
                                                $contentType = $_SERVER["HTTP_CONTENT_TYPE"];

                                        if (isset($_SERVER["CONTENT_TYPE"]))
                                                $contentType = $_SERVER["CONTENT_TYPE"];

                                        // Handle non multipart uploads older WebKit versions didn't support multipart in HTML5
                                        if (strpos($contentType, "multipart") !== false) {
                                                if (isset($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
                                                        // Open temp file
                                                        $out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
                                                        if ($out) {
                                                                // Read binary input stream and append it to temp file
                                                                $in = fopen($_FILES['file']['tmp_name'], "rb");

                                                                if ($in) {
                                                                        while ($buff = fread($in, 4096))
                                                                                fwrite($out, $buff);
                                                                } else
                                                                    die('{"jsonrpc" : "2.0", "result" : "' . $translator->translate("Failed to open input stream") . '", "id" : "id", "type" : "error"}');

                                                                fclose($out);
                                                                fclose($in);
                                                                @unlink($_FILES['file']['tmp_name']);
                                                        } else
                                                                die('{"jsonrpc" : "2.0", "result" : "' . $translator->translate("Failed to open output stream.") . '", "id" : "id", "type" : "error"}');
                                                } else
                                                        die('{"jsonrpc" : "2.0", "result" : "' . $translator->translate("Failed to move uploaded file") . '", "id" : "id", "type" : "error"}');
                                        } else {
                                                // Open temp file
                                                $out = fopen($targetDir . DIRECTORY_SEPARATOR . $fileName, $chunk == 0 ? "wb" : "ab");
                                                if ($out) {
                                                        // Read binary input stream and append it to temp file
                                                        $in = fopen("php://input", "rb");

                                                        if ($in) {
                                                                while ($buff = fread($in, 4096))
                                                                        fwrite($out, $buff);
                                                        } else
                                                                die('{"jsonrpc" : "2.0", "result" : "' . $translator->translate("Failed to open input stream") . '", "id" : "id", "type" : "error"}');

                                                        fclose($out);
                                                        fclose($in);
                                                } else
                                                        die('{"jsonrpc" : "2.0", "result" : "' . $translator->translate("Failed to open output stream") . '", "id" : "id", "type" : "error"}');
                                        }

                                        // Return JSON-RPC response
                                        die('{"jsonrpc" : "2.0", "result" : "' . $translator->translate("Successfuly uploaded") . '", "id" : "id", "type" : "info"}');
                            } else
                                die('{"jsonrpc" : "2.0", "result" : "' . $translator->translate("Target directory is not available") . '", "id" : "id", "type" : "error"}');

                    }

        }

    }

    public function handleRefreshMessage()
    {
        $request = Environment::getHttpRequest();
        $type = $request->getQuery('type');
        $text = $request->getQuery('message');
        parent::getParent()->flashMessage($text, $type);
    }

    public function render()
    {
        $namespace = Environment::getSession('file-manager');
        $template = $this->template;
        $template->setFile(__DIR__ . '/FMUpload.latte');

        // set language
        $lang_file = __DIR__ . '/../locale/FileManager.'. $this->config['lang'].'.mo';
        if (file_exists($lang_file))
            $template->setTranslator(new GettextTranslator($lang_file));
        else
             throw new Exception ("Language file " . $lang_file . " doesn't exist! Application can not be loaded!");

        $size = 0;
        foreach (Finder::findFiles('*')->from($this->config['uploadroot'] . $this->config['uploadpath']) as $file) {
                           $size += $file->getSize();
        }

        if ($this->config['quota'] == True) {
            $limit = $this->config['quota_limit'] * 1048576;
            $freespace = $limit - $size;
            $percentage = ($freespace / $limit)*100;
        } else {
            $freespace = disk_free_space($this->config['uploadroot']);
            $percentage = ($freespace / disk_total_space($this->config['uploadroot']))*100;
        }

        if ( $freespace <= 0 )
            parent::getParent()->flashMessage(
                    $translator->translate("Disk is full! Files will not be uploaded"),
                    'warning'
            );
        elseif ($percentage <= 5)
            parent::getParent()->flashMessage(
                        $translator->translate("Be careful, less than 5% of free space on disk left"),
                        'warning'
            );

        if ($this->config['readonly'] == True)
            parent::getParent()->flashMessage(
                        $translator->translate("File manager is in read-only mode. Files will not be uploaded"),
                        'warning'
            );

        $template->config = $this->config;
        $template->actualdir = $namespace->actualdir;

        $template->render();
    }
}