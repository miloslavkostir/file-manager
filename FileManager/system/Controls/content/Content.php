<?php

namespace Ixtrum\FileManager\Controls;

use Nette\Application\Responses\FileResponse;

class Content extends \Ixtrum\FileManager
{

    public function handleShowFileInfo($filename = "")
    {
        // if sended by AJAX
        if (!$filename) {
            $filename = $this->presenter->context->httpRequest->getQuery("filename");
        }

        if ($filename) {

            $actualdir = $this->context->application->getActualDir();
            if ($this->context->filesystem->validPath($actualdir, $filename)) {

                $this->parent->parent->template->fileinfo = $actualdir;
                $this["control-fileInfo"]->filename = $filename;
            } else {
                $this->parent->parent->flashMessage($this->context->translator->translate("File %s not found!", $filename), "warning");
            }
        } else {
            $this->parent->parent->flashMessage($this->context->translator->translate("Incorrect input data!"), "error");
        }
    }

    public function handleShowMultiFileInfo($files = array())
    {
        $actualdir = $this->context->application->getActualDir();

        // if sended by AJAX
        if (!$files) {
            $files = $this->presenter->context->httpRequest->getPost("files");
        }

        if (is_array($files) && $files) {

            $info = $this->context->filesystem->getFilesInfo($actualdir, $files, true);
            $this->presenter->payload->result = "success";
            $this->presenter->payload->size = \Nette\Templating\Helpers::bytes($info["size"]);
            $this->presenter->payload->dirCount = $info["dirCount"];
            $this->presenter->payload->fileCount = $info["fileCount"];
            $this->presenter->sendPayload();
        } else {
            $this->parent->parent->flashMessage($this->context->translator->translate("Incorrect input data!"), "error");
        }
    }

    public function handleCopyToClipboard($filename = "")
    {
        // if sended by AJAX
        if (!$filename) {
            $filename = $this->presenter->context->httpRequest->getPost("filename");
        }

        if ($filename) {

            $actualdir = $this->context->application->getActualDir();
            if ($this->context->filesystem->validPath($actualdir, $filename)) {

                $this->context->clipboard->add(
                        $actualdir . $filename,
                        array(
                            "action" => "copy",
                            "actualdir" => $actualdir,
                            "filename" => $filename
                        )
                );
            } else {
                $this->parent->parent->flashMessage($this->context->translator->translate("File %s not found!", $filename), "warning");
            }
        } else {
            $this->parent->parent->flashMessage($this->context->translator->translate("Incorrect input data!"), "error");
        }
    }

    public function handleMultiCopyToClipboard($files = array())
    {
        // if sended by AJAX
        if (!$files) {
            $files = $this->presenter->context->httpRequest->getPost("files");
        }

        if (is_array($files) && $files) {

            $actualdir = $this->context->application->getActualDir();
            foreach ($files as $file) {

                $this->context->clipboard->add(
                        $actualdir . $file,
                        array(
                            "action" => "copy",
                            "actualdir" => $actualdir,
                            "filename" => $file
                        )
                );
            }
        } else {
            $this->parent->parent->flashMessage($this->context->translator->translate("Incorrect input data!"), "error");
        }
    }

    public function handleCutToClipboard($filename = "")
    {
        // if sended by AJAX
        if (!$filename) {
            $filename = $this->presenter->context->httpRequest->getPost("filename");
        }

        if ($filename) {

            $actualdir = $this->context->application->getActualDir();
            if ($this->context->filesystem->validPath($actualdir, $filename)) {

                $this->context->clipboard->add(
                        $actualdir . $filename,
                        array(
                            "action" => "cut",
                            "actualdir" => $actualdir,
                            "filename" => $filename
                        )
                );
            } else {
                $this->parent->parent->flashMessage($this->context->translator->translate("File %s not found!", $filename), "warning");
            }
        } else {
            $this->parent->parent->flashMessage($this->context->translator->translate("Incorrect input data!"), "error");
        }
    }

    public function handleMultiCutToClipboard($files = array())
    {
        // if sended by AJAX
        if (!$files) {
            $files = $this->presenter->context->httpRequest->getPost("files");
        }

        if (is_array($files) && $files) {

            $actualdir = $this->context->application->getActualDir();
            foreach ($files as $file) {

                $this->context->clipboard->add(
                        $actualdir . $file,
                        array(
                            "action" => "cut",
                            "actualdir" => $actualdir,
                            "filename" => $file
                        )
                );
            }
        } else {
            $this->parent->parent->flashMessage($this->context->translator->translate("Incorrect input data!"), "error");
        }
    }

    public function handleDelete($filename = "")
    {
        // if sended by AJAX
        if (!$filename) {
            $filename = $this->presenter->context->httpRequest->getQuery("filename");
        }

        $actualdir = $this->context->application->getActualDir();
        if ($this->context->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->context->translator->translate("Read-only mode enabled!"), "warning");
        } else {

            if ($filename) {

                if ($this->context->filesystem->validPath($actualdir, $filename)) {

                    if ($this->context->filesystem->delete($actualdir, $filename)) {
                        $this->parent->parent->flashMessage($this->context->translator->translate("Successfuly deleted - %s", $filename), "info");
                    } else {
                        $this->parent->parent->flashMessage($this->context->translator->translate("An error occured!"), "error");
                    }
                } else {
                    $this->parent->parent->flashMessage($this->context->translator->translate("File %s not found!", $filename), "warning");
                }
            } else {
                $this->parent->parent->flashMessage($this->context->translator->translate("Incorrect input data!"), "error");
            }
        }

        $this->handleShowContent($actualdir);
    }

    public function handleMultiDelete($files = array())
    {
        // if sended by AJAX
        if (!$files) {
            $files = $this->presenter->context->httpRequest->getPost("files");
        }

        if ($this->context->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->context->translator->translate("Read-only mode enabled!"), "warning");
        } else {

            $actualdir = $this->context->application->getActualDir();
            if (is_array($files) && $files) {

                foreach ($files as $file) {

                    if ($this->context->filesystem->delete($actualdir, $file)) {
                        $this->parent->parent->flashMessage($this->context->translator->translate("Successfuly deleted - %s", $file), "info");
                    } else {
                        $this->parent->parent->flashMessage($this->context->translator->translate("An error occured - %s", $file), "error");
                    }
                }
            } else {
                $this->parent->parent->flashMessage($this->context->translator->translate("Incorrect input data!"), "error");
            }

            $this->handleShowContent($actualdir);
        }
    }

    public function handleZip($files = array())
    {
        // if sended by AJAX
        if (!$files) {
            $files = $this->presenter->context->httpRequest->getPost("files");
        }

        if ($this->context->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->context->translator->translate("Read-only mode enabled!"), "warning");
        } else {

            $actualdir = $this->context->application->getActualDir();
            $actualPath = $this->context->filesystem->getAbsolutePath($actualdir);

            $zip = new \Ixtrum\FileManager\Application\Zip($this->context->parameters, $actualPath);
            $zip->addFiles($files);

            $key = $this->context->filesystem->getRealPath($actualPath);
            if ($this->context->parameters["cache"]) {
                $this->parent->parent->context->caching->deleteItem(array("content", $key));
            }
        }

        $this->handleShowContent($actualdir);
    }

    public function handleOrderBy($key)
    {
        $session = $this->presenter->context->session->getSection("file-manager");
        $session->order = $key;

        $actualdir = $this->context->application->getActualDir();
        $absPath = $this->context->filesystem->getRealPath($this->context->filesystem->getAbsolutePath($actualdir));

        if ($this->context->parameters["cache"]) {
            $this->context->caching->deleteItem(array("content", $absPath));
        }

        $this->handleShowContent($actualdir);
    }

    public function handleRunPlugin($plugin, $files = "")
    {
        $this->parent->parent->handleRunPlugin($plugin);
    }

    public function handleDownloadFile($filename = "")
    {
        $actualdir = $this->context->application->getActualDir();

        // if sended by AJAX
        if (!$filename) {
            $filename = $this->presenter->context->httpRequest->getQuery("filename");
        }

        if ($filename) {

            if ($this->context->filesystem->validPath($actualdir, $filename)) {

                $path = $this->context->filesystem->getAbsolutePath($actualdir) . $filename;
                $this->presenter->sendResponse(new FileResponse($path, NULL, NULL));
            } else {
                $this->parent->parent->flashMessage($this->context->translator->translate("File %s not found!", $filename), "warning");
            }
        } else {
            $this->parent->parent->flashMessage($this->context->translator->translate("Incorrect input data!"), "error");
        }
    }

    public function handleGoToParent()
    {
        $actualdir = $this->context->application->getActualDir();
        $parent = dirname($actualdir);

        if ($parent == "\\" || $parent == ".") {
            $parent_path = $this->context->filesystem->getRootname();
        } else {
            $parent_path = $parent . "/";
        }

        $this->handleShowContent($parent_path);
    }

    public function handleMove($targetdir = "", $filename = "")
    {
        $request = $this->presenter->context->httpRequest;

        // if sended by AJAX
        if (!$targetdir) {
            $targetdir = $request->getQuery("targetdir");
        }

        if (!$filename) {
            $filename = $request->getQuery("filename");
        }

        if ($this->context->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->context->translator->translate("Read-only mode enabled!"), "warning");
        } else {

            $actualdir = $this->context->application->getActualDir();
            if ($targetdir && $filename) {

                if ($this->context->filesystem->move($actualdir, $targetdir, $filename)) {

                    $this->presenter->payload->result = "success";
                    $this->parent->parent->flashMessage($this->context->translator->translate("Successfuly moved - %s", $filename), "info");
                } else {
                    $this->parent->parent->flashMessage($this->context->translator->translate("An error occured. File %s was not moved.", $filename), "error");
                }
            }

            $this->handleShowContent($actualdir);
        }
    }

    public function handleShowContent($actualdir)
    {
        $this->parent->parent->handleShowContent($actualdir);
    }

    public function handleShowThumb($dir, $file)
    {
        $path = $this->context->filesystem->getAbsolutePath($dir) . $file;
        $thumb = $this->context->thumbs->getThumbFile($path);
        $thumb->send();
    }

    public function render()
    {
        $template = $this->template;
        $session = $this->presenter->context->session->getSection("file-manager");
        $actualdir = $this->context->application->getActualDir();

        $view = $session->view;
        $mask = $session->mask;
        $order = $session->order;

        if (!$mask) {
            $mask = "*";
        }

        if (!$order) {
            $order = "type";
        }

        $allowedViews = array("details", "large", "list", "small");
        if ($view && in_array($view, $allowedViews)) {
            $template->setFile(__DIR__ . "/$view.latte");
        } else {

            $template->setFile(__DIR__ . "/large.latte");
            $view = "large";
        }

        $template->setTranslator($this->context->translator);

        $template->files = $this->loadData($actualdir, $mask, $view, $order);
        $template->actualdir = $actualdir;
        $template->rootname = $this->context->filesystem->getRootName();
        $template->thumb_dir = $this->context->parameters["resDir"] . "img/icons/" . $view . "/";

        // Load plugins
        if ($this->context->parameters["plugins"]) {

            $contextPlugins = array();
            foreach ($this->context->parameters["plugins"] as $plugin) {

                if ($plugin["contextPlugin"]) {
                    $contextPlugins[] = $plugin;
                }
            }

            if ($contextPlugins) {
                $template->plugins = $contextPlugins;
            }
        }

        $template->render();
    }

    /**
     * Load directory content
     *
     * @todo Nette Finder does not support mask for folders
     *
     * @param string $actualdir
     * @param string $mask
     * @param string $view
     * @param string $order
     *
     * @return array
     */
    private function getDirectoryContent($actualdir, $mask, $view, $order)
    {
        $supportedThumbs = $this->context->thumbs->supported;
        $absolutePath = $this->context->filesystem->getAbsolutePath($actualdir);

        $files = \Ixtrum\FileManager\Application\FileSystem\Finder::find($mask)
                ->in($absolutePath)
                ->orderBy($order);

        $dir_array = array();
        foreach ($files as $file) {

            $name = $file->getFilename();
            $dir_array[$name]["filename"] = $name;
            $dir_array[$name]["modified"] = $file->getMTime();

            if (!is_dir($file->getPath() . "/$name")) {

                $dir_array[$name]["type"] = "file";
                $dir_array[$name]["size"] = $this->context->filesystem->filesize($file->getPathName());
                $filetype = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $dir_array[$name]["filetype"] = $filetype;

                if (file_exists($this->context->parameters["wwwDir"] . $this->context->parameters["resDir"] . "img/icons/$view/$filetype.png")) {

                    if ($filetype === "folder") {
                        $dir_array[$name]["icon"] = "icon.png";
                    } else {
                        $dir_array[$name]["icon"] = "$filetype.png";
                    }

                    if (in_array($filetype, $supportedThumbs)) {
                        $dir_array[$name]["create_thumb"] = true;
                    } else {
                        $dir_array[$name]["create_thumb"] = false;
                    }
                } else {

                    $dir_array[$name]["icon"] = "icon.png";
                    $dir_array[$name]["create_thumb"] = false;
                }
            } else {
                $dir_array[$name]["type"] = "folder";
                $dir_array[$name]["icon"] = "folder.png";
                $dir_array[$name]["create_thumb"] = false;
            }
        }

        return $dir_array;
    }

    /**
     * Load data from actual directory
     *
     * @internal
     * @param string $actualdir
     * @param string $mask
     * @param string $view
     * @param string $order
     * @return array
     */
    private function loadData($actualdir, $mask, $view, $order)
    {
        if ($this->context->parameters["cache"]) {

            $absDir = $this->context->filesystem->getRealPath($this->context->filesystem->getAbsolutePath($actualdir));
            $cacheData = $this->context->caching->getItem(array("content", $absDir));

            if (!$cacheData) {

                $output = $this->getDirectoryContent($actualdir, $mask, $view, $order);
                $this->context->caching->saveItem(array("content", $absDir), $output);
                return $output;
            } else {
                return $cacheData;
            }
        } else {
            return $this->getDirectoryContent($actualdir, $mask, $view, $order);
        }
    }

}