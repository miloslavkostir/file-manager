<?php

namespace Ixtrum;

use Nette\Utils\Finder,
        Nette\Templating\Helpers,
        Nette\Application\Responses\FileResponse;


class Content extends FileManager
{
        public function __construct($userConfig)
        {
                parent::__construct($userConfig);
        }


        public function handleShowFileInfo($filename = "")
        {
                // if sended by AJAX
                if (!$filename)
                        $filename = $this->presenter->context->httpRequest->getQuery("filename");

                parent::getParent()->handleShowFileInfo($filename);
        }


        public function handleShowMultiFileInfo($files = "")
        {
                $actualdir = $this->context->system->getActualDir();

                // if sended by AJAX
                if (!$files)
                        $files = $this->presenter->context->httpRequest->getPost("files");

                if (is_array($files)) {

                        $info = $this->context->files->getFilesInfo($actualdir, $files, true);
                        $this->presenter->payload->result = "success";
                        $this->presenter->payload->size = Helpers::bytes($info["size"]);
                        $this->presenter->payload->dirCount = $info["dirCount"];
                        $this->presenter->payload->fileCount = $info["fileCount"];
                        $this->presenter->sendPayload();
                } else
                        parent::getParent()->flashMessage("Incorrect input type data. Must be an array!", "error");
        }


        public function handleCopyToClipboard($filename = "")
        {
                // if sended by AJAX
                if (!$filename)
                        $filename = $this->presenter->context->httpRequest->getPost("filename");

                $session = $this->presenter->context->session->getSection("file-manager");
                $actualdir = $this->context->system->getActualDir();

                if ($this->context->tools->validPath($actualdir, $filename)) {

                        $session->clipboard[$actualdir.$filename] = array(
                            "action" => "copy",
                            "actualdir" => $actualdir,
                            "filename" => $filename
                        );

                        $this->handleShowContent($actualdir);
                } else
                        parent::getParent()->flashMessage("File $actualdir$filename already does not exist!", "warning");
        }


        public function handleMultiCopyToClipboard($files = "")
        {
                $session = $this->presenter->context->session->getSection("file-manager");
                $actualdir = $this->context->system->getActualDir();

                // if sended by AJAX
                if (!$files)
                        $files = $this->presenter->context->httpRequest->getPost("files");

                if (is_array($files)) {

                        foreach($files as $file) {
                                $session->clipboard[$actualdir.$file] = array(
                                    "action" => "copy",
                                    "actualdir" => $actualdir,
                                    "filename" => $file
                                );
                        }
                } else
                        parent::getParent()->flashMessage("Incorrect input type data. Must be an array!", "error");

                parent::getParent()->refreshSnippets(array("clipboard"));
        }


        public function handleCutToClipboard($filename = "")
        {
                // if sended by AJAX
                if (!$filename)
                        $filename = $this->presenter->context->httpRequest->getPost("filename");

                $session = $this->presenter->context->session->getSection("file-manager");
                $actualdir = $this->context->system->getActualDir();

                if ($this->context->tools->validPath($actualdir, $filename)) {

                        $session->clipboard[$actualdir.$filename] = array(
                            "action" => "cut",
                            "actualdir" => $actualdir,
                            "filename" => $filename
                        );

                        $this->handleShowContent($actualdir);
                }
        }

        public function handleMultiCutToClipboard($files = "")
        {
                $session = $this->presenter->context->session->getSection("file-manager");
                $actualdir = $this->context->system->getActualDir();

                // if sended by AJAX
                if (!$files)
                        $files = $this->presenter->context->httpRequest->getPost("files");

                if (is_array($files)) {

                        foreach($files as $file) {

                                $session->clipboard[$actualdir.$file] = array(
                                    "action" => "cut",
                                    "actualdir" => $actualdir,
                                    "filename" => $file
                                );
                        }
                } else
                        parent::getParent()->flashMessage("Incorrect input type data. Must be an array!", "error");

                parent::getParent()->refreshSnippets(array("clipboard"));
        }


        public function handleDelete($filename = "")
        {
                $actualdir = $this->context->system->getActualDir();

                // if sended by AJAX
                if (!$filename)
                        $filename = $this->presenter->context->httpRequest->getQuery("filename");

                if ($this->context->parameters["readonly"])
                        parent::getParent()->flashMessage("Read-only mode enabled!", "warning");
                else {

                        if ($this->context->tools->validPath($actualdir, $filename)) {

                            if ($this->context->files->delete($actualdir, $filename))
                                parent::getParent()->flashMessage("Successfuly deleted", "info");
                            else
                                parent::getParent()->flashMessage("An error occured!", "error");
                        } else
                                parent::getParent()->flashMessage("File $actualdir$filename already does not exist!", "warning");
                }

                $this->handleShowContent($actualdir);
        }


        public function handleMultiDelete($files = "")
        {
                $actualdir = $this->context->system->getActualDir();

                // if sended by AJAX
                if (!$files)
                        $files = $this->presenter->context->httpRequest->getPost("files");

                if ($this->context->parameters["readonly"])
                        parent::getParent()->flashMessage("Read-only mode enabled!", "warning");
                else {

                        if (is_array($files)) {

                                foreach($files as $file) {

                                        if ($this->context->files->delete($actualdir, $file))
                                                parent::getParent()->flashMessage("Successfuly deleted", "info");
                                        else
                                                parent::getParent()->flashMessage("An error occured!", "error");
                                }
                        } else
                                parent::getParent()->flashMessage("Incorrect input type data. Must be an array!", "error");

                        $this->handleShowContent($actualdir);
                }
        }


        public function handleZip($files = "")
        {
                // if sended by AJAX
                if (!$files)
                        $files = $this->presenter->context->httpRequest->getPost("files");


                if ($this->context->parameters["readonly"])
                        parent::getParent()->flashMessage("Read-only mode enabled!", "warning");
                else {

                        $actualdir = $this->context->system->getActualDir();
                        $actualPath = $this->context->tools->getAbsolutePath($actualdir);


                        $zip = new System\Zip($actualPath);
                        $zip->addFiles($files);


                        $key = $this->context->tools->getRealPath($actualPath);
                        if ($this->context->parameters["cache"])
                                parent::getParent()->context->caching->deleteItem(array("content", $key));
                }

                parent::getParent()->refreshSnippets(array("message"));
                $this->handleShowContent($actualdir);
        }


        public function handleOrderBy($key)
        {
                $session = $this->presenter->context->session->getSection("file-manager");
                $session->order = $key;

                $tools = $this->context->tools;
                $actualdir = $this->context->system->getActualDir();
                $absPath = $tools->getRealPath($tools->getAbsolutePath($actualdir));

                if ($this->context->parameters["cache"])
                        $this->context->caching->deleteItem(array("content", $absPath));

                parent::getParent()->handleShowContent($actualdir);
        }


        public function handleRunPlugin($plugin, $files = "")
        {
                parent::getParent()->handleRunPlugin($plugin);
        }


        public function handleDownloadFile($filename = "")
        {
                $actualdir = $this->context->system->getActualDir();

                // if sended by AJAX
                if (!$filename)
                        $filename = $this->presenter->context->httpRequest->getQuery("filename");

                if ($this->context->tools->validPath($actualdir, $filename)) {

                        $path = $this->context->tools->getAbsolutePath($actualdir) . $filename;
                        $this->presenter->sendResponse(new FileResponse($path, NULL, NULL));
                } else
                        parent::getParent()->flashMessage("File $actualdir$filename already does not exist!", "warning");
        }


        public function handleGoToParent()
        {
                $actualdir = $this->context->system->getActualDir();
                $parent = dirname($actualdir);

                if ($parent == "\\" || $parent == ".")
                        $parent_path = $this->context->tools->getRootname();
                else
                        $parent_path = $parent . "/";

                $this->handleShowContent($parent_path);
        }

        public function handleMove($targetdir = "", $filename = "")
        {
                $session = $this->presenter->context->session->getSection("file-manager");
                $actualdir = $session->actualdir;
                $request = $this->presenter->context->httpRequest;

                // if sended by AJAX
                if (!$targetdir)
                    $targetdir = $request->getQuery("targetdir");
                if (!$filename)
                    $filename = $request->getQuery("filename");

                if ($this->context->parameters["readonly"])
                        parent::getParent()->flashMessage("Read-only mode enabled!!", "warning");
                else {

                        if ($this->context->files->move($actualdir, $targetdir, $filename)) {

                                $this->presenter->payload->result = "success";
                                parent::getParent()->flashMessage("Successfuly moved.", "info");
                                parent::getParent()->handleShowContent($targetdir);
                        } else {

                                parent::getParent()->flashMessage("An error occured. File was not moved.", "error");
                                parent::getParent()->handleShowContent($actualdir);
                        }
                }
        }


        public function handleShowContent($actualdir)
        {
                parent::getParent()->handleShowContent($actualdir);
        }


        public function handleShowThumb($dir, $file)
        {
                $path = $this->context->tools->getAbsolutePath($dir) . $file;
                $thumb = $this->context->thumbs->getThumb($path);
                $thumb->send();
        }


        public function render()
        {
                $template = $this->template;
                $session = $this->presenter->context->session->getSection("file-manager");
                $actualdir = $session->actualdir;

                $view = $session->view;
                $mask = $session->mask;
                $order = $session->order;

                $plugins = $this->context->plugins->loadPlugins();

                if (!$mask)
                    $mask = "*";

                if (!$order)
                    $order = "type";

                if ($view) {

                        $c_template = __DIR__ . "/" . $view . ".latte";
                        if (file_exists($c_template))
                                $template->setFile($c_template);
                        else {

                                $template->setFile(__DIR__ . "/large.latte");
                                $view = "large";
                        }
                } else {
                        $template->setFile(__DIR__ . "/large.latte");
                        $view = "large";
                }

                $template->files = $this->loadData($actualdir, $mask, $view, $order);
                $template->actualdir = $actualdir;
                $template->rootname = $this->context->tools->getRootName();
                $template->thumb_dir = $this->context->parameters["resource_dir"] . "img/icons/" . $view . "/";

                if ($this->plugins) {

                        $contextPlugins = array();
                        foreach($plugins as $plugin) {

                                if ($plugin["contextPlugin"])
                                        $contextPlugins[] = $plugin;
                        }

                        if ($contextPlugins)
                                $template->plugins = $contextPlugins;
                }

                $template->render();
        }


        /**
         * Load directory content
         * TODO Nette Finder does not support mask for folders
         *
         * @serializationVersion 1
         * 
         * @param string $actualdir
         * @param string $mask
         * @param string $view
         * @param string $order
         * @return array
         */
        private function getDirectoryContent($actualdir, $mask, $view, $order)
        {
                $thumb_dir = $this->context->parameters["resource_dir"] . "img/icons/" . $view . "/";
                if (!file_exists($this->presenter->context->parameters["wwwDir"] . $thumb_dir))
                        throw new \Nette\DirectoryNotFoundException("Missing folder with icons for '$view' view");

                $tools = $this->context->tools;
                $uploadpath = $this->context->parameters["uploadpath"];
                $rootname = $tools->getRootName();
                $uploadroot = $this->context->parameters["uploadroot"];
                $supportedThumbs = $this->context->thumbs->supported;
                $absolutePath = $tools->getAbsolutePath($actualdir);

                $files = SortedFinder::find($mask)
                            ->in($absolutePath)
                            ->orderBy($order);

                $dir_array = array();
                foreach( $files as $file ) {

                        $name = $file->getFilename();
                        $dir_array[$name]["filename"] =  $name;
                        $dir_array[$name]["modified"] =  $file->getMTime();

                        if (!is_dir($file->getPath() . "/$name")) {

                                $dir_array[$name]["type"] = "file";
                                $dir_array[$name]["size"] =  $this->context->files->filesize($file->getPathName());
                                $filetype = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                                $dir_array[$name]["filetype"] = $filetype;

                                if (file_exists($this->presenter->context->parameters["wwwDir"] . $thumb_dir . $filetype . ".png")) {

                                        $dir_array[$name]["icon"] =  $filetype . ".png";
                                        if (in_array($filetype, $supportedThumbs))
                                            $dir_array[$name]["create_thumb"] =  true;
                                        else
                                            $dir_array[$name]["create_thumb"] =  false;
                                } else {

                                        $dir_array[$name]["icon"] =  "icon.png";
                                        $dir_array[$name]["create_thumb"] =  false;
                                }

                        } else {
                                $dir_array[ $name ]["type"] = "folder";
                                $dir_array[ $name ]["icon"] =  "folder.png";
                                $dir_array[ $name ]["create_thumb"] =  False;
                        }
                }

                return $dir_array;
        }


        /**
         * Load data
         *
         * @param string $actualdir
         * @param string $mask
         * @param string $view
         * @param string $order
         * @return array
         */
        private function loadData($actualdir, $mask, $view, $order)
        {
                if ($this->context->parameters["cache"]) {

                        $tools = $this->context->tools;
                        $absDir = $tools->getRealPath($tools->getAbsolutePath($actualdir));
                        $caching = $this->context->caching;
                        $cacheData = $caching->getItem(array("content",  $absDir));

                        if (!$cacheData) {

                                $output = $this->getDirectoryContent($actualdir, $mask, $view, $order);
                                $caching->saveItem(array("content",  $absDir), $output);
                                return $output;
                        } else
                                return $cacheData;
                } else
                        return $this->getDirectoryContent($actualdir, $mask, $view, $order);
        }
}