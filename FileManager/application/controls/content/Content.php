<?php

namespace Ixtrum\FileManager\Application\Controls;

use Nette\Application\Responses\FileResponse,
    Ixtrum\FileManager\Application\FileSystem\Finder;

class Content extends \Ixtrum\FileManager\Application\Controls
{

    public function handleInfo()
    {
        $this->parent->parent->template->fileinfo = $this->getActualDir();
    }

    public function handleCopy()
    {
        foreach ($this->selectedFiles as $file) {

            $this->system->session->add(
                    "clipboard",
                    $this->getActualDir() . $file,
                    array(
                        "action" => "copy",
                        "actualdir" => $this->getActualDir(),
                        "filename" => $file
                    )
            );
        }
    }

    public function handleCut()
    {
        foreach ($this->selectedFiles as $file) {

            $this->system->session->add(
                    "clipboard",
                    $this->getActualDir() . $file,
                    array(
                        "action" => "cut",
                        "actualdir" => $this->getActualDir(),
                        "filename" => $file
                    )
            );
        }
    }

    public function handleDelete()
    {
        if ($this->system->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Read-only mode enabled!"), "warning");
        } else {

            foreach ($this->selectedFiles as $file) {

                if ($this->system->filesystem->delete($this->getActualDir(), $file)) {
                    $this->parent->parent->flashMessage($this->system->translator->translate("Successfuly deleted - %s", $file), "info");
                } else {
                    $this->parent->parent->flashMessage($this->system->translator->translate("An error occured - %s", $file), "error");
                }
            }
        }
    }

    public function handleZip()
    {
        if ($this->system->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Read-only mode enabled!"), "warning");
        } else {

            $actualPath = $this->system->filesystem->getAbsolutePath($this->getActualDir());
            $zip = new \Ixtrum\FileManager\Application\Zip($this->system->parameters, $actualPath);
            $zip->addFiles($this->selectedFiles);

            $key = realpath($actualPath);
            if ($this->system->parameters["cache"]) {
                $this->parent->parent->system->caching->deleteItem(array("content", $key));
            }
        }
    }

    public function handleOrderBy($key)
    {
        $this->system->session->set("order", $key);

        $absPath = realpath($this->system->filesystem->getAbsolutePath($this->getActualDir()));
        if ($this->system->parameters["cache"]) {
            $this->system->caching->deleteItem(array("content", $absPath));
        }
    }

    public function handleRunPlugin($name)
    {
        $this->parent->parent->handleRunPlugin($name);
    }

    public function handleDownload()
    {
        if (count($this->selectedFiles) === 1) {

            $file = $this->selectedFiles[0];
            if ($this->system->filesystem->validPath($this->getActualDir(), $file)) {

                $path = $this->system->filesystem->getAbsolutePath($this->getActualDir()) . $file;
                $this->presenter->sendResponse(new FileResponse($path, $file, null));
            } else {
                $this->parent->parent->flashMessage($this->system->translator->translate("File %s not found!", $file), "warning");
            }
        }
    }

    public function handleGoToParent()
    {
        $parent = dirname($this->getActualDir());
        if ($parent == "\\" || $parent == ".") {
            $parentDir = $this->system->filesystem->getRootname();
        } else {
            $parentDir = $parent . "/";
        }
        $this->setActualDir($parentDir);
    }

    public function handleMove($targetdir = "", $filename = "")
    {
        // if sended by AJAX
        if (!$targetdir) {
            $targetdir = $this->presenter->context->httpRequest->getQuery("targetdir");
        }

        if (!$filename) {
            $filename = $this->presenter->context->httpRequest->getQuery("filename");
        }

        if ($this->system->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Read-only mode enabled!"), "warning");
        } else {

            if ($targetdir && $filename) {

                if ($this->system->filesystem->move($this->getActualDir(), $targetdir, $filename)) {

                    $this->presenter->payload->result = "success";
                    $this->parent->parent->flashMessage($this->system->translator->translate("Successfuly moved - %s", $filename), "info");
                } else {
                    $this->parent->parent->flashMessage($this->system->translator->translate("An error occured. File %s was not moved.", $filename), "error");
                }
            }
        }
    }

    public function handleOpenDir($dir)
    {
        $this->setActualDir($dir);
    }

    public function handleShowThumb($dir, $file)
    {
        $path = $this->system->filesystem->getAbsolutePath($dir) . $file;
        $thumb = $this->system->thumbs->getThumbFile($path);
        $thumb->send();
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . "/$this->view.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->files = $this->loadData(
            $this->getActualDir(),
            $this->system->session->get("mask"),
            $this->view,
            $this->system->session->get("order")
        );
        $this->template->actualdir = $this->getActualDir();
        $this->template->rootname = $this->system->filesystem->getRootName();
        $this->template->thumb_dir = $this->system->parameters["resDir"] . "img/icons/$this->view/";

        // Load plugins
        if ($this->system->parameters["plugins"]) {

            $contextPlugins = array();
            foreach ($this->system->parameters["plugins"] as $plugin) {

                if ($plugin["contextPlugin"]) {
                    $contextPlugins[] = $plugin;
                }
            }

            if ($contextPlugins) {
                $this->template->plugins = $contextPlugins;
            }
        }

        $this->template->render();
    }

    /**
     * Load directory content
     *
     * @todo Nette Finder does not support mask for folders
     *
     * @param string $dir
     * @param string $mask
     * @param string $view
     * @param string $order
     *
     * @return array
     */
    private function getDirectoryContent($dir, $mask, $view, $order)
    {
        $absolutePath = $this->system->filesystem->getAbsolutePath($dir);

        $files = Finder::find($mask)
                ->in($absolutePath)
                ->orderBy($order);

        $dir_array = array();
        foreach ($files as $file) {

            $name = $file->getFilename();
            $dir_array[$name]["filename"] = $name;
            $dir_array[$name]["modified"] = $file->getMTime();

            if (!is_dir($file->getPath() . "/$name")) {

                $dir_array[$name]["type"] = "file";
                $dir_array[$name]["size"] = $this->system->filesystem->filesize($file->getPathName());
                $filetype = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $dir_array[$name]["filetype"] = $filetype;

                if (file_exists($this->system->parameters["wwwDir"] . $this->system->parameters["resDir"] . "img/icons/$view/$filetype.png")) {

                    if ($filetype === "folder") {
                        $dir_array[$name]["icon"] = "icon.png";
                    } else {
                        $dir_array[$name]["icon"] = "$filetype.png";
                    }

                    if (in_array($filetype, $this->system->thumbs->supported)) {
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
     * @param string $dir
     * @param string $mask
     * @param string $view
     * @param string $order
     *
     * @return array
     */
    private function loadData($dir, $mask, $view, $order)
    {
        // Default filter mask
        if (empty($mask)) {
            $mask = "*";
        }

        if ($this->system->parameters["cache"] && $mask === "*") {

            $absDir = realpath($this->system->filesystem->getAbsolutePath($dir));
            $cacheData = $this->system->caching->getItem(array("content", $absDir));

            if (!$cacheData) {

                $output = $this->getDirectoryContent($dir, $mask, $view, $order);
                $this->system->caching->saveItem(array("content", $absDir), $output);
                return $output;
            } else {
                return $cacheData;
            }
        } else {
            return $this->getDirectoryContent($dir, $mask, $view, $order);
        }
    }

}