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
                    "clipboard", $this->getActualDir() . $file, array(
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
                    "clipboard", $this->getActualDir() . $file, array(
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
            return;
        }

        foreach ($this->selectedFiles as $file) {

            $path = $this->getAbsolutePath($this->getActualDir()) . DIRECTORY_SEPARATOR . $file;
            if (!file_exists($path)) {
                $this->parent->parent->flashMessage($this->system->translator->translate("'%s' already does not exist!", $file), "warning");
                continue;
            }

            if (!$this->system->filesystem->delete($path)) {
                $this->parent->parent->flashMessage($this->system->translator->translate("It's not possible to delete '%s'!", $file), "warning");
                continue;
            }

            // Clear cache if needed
            if ($this->system->parameters["cache"]) {

                if (is_dir($path)) {
                    $this->system->caching->deleteItemsRecursive($path);
                }
                $this->system->caching->deleteItem(null, array("tags" => "treeview"));
                $this->system->caching->deleteItem(array("content", dirname($path)));
            }

            $this->parent->parent->flashMessage($this->system->translator->translate("'%s' successfuly deleted.", $file));
        }
    }

    public function handleZip()
    {
        if ($this->system->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Read-only mode enabled!"), "warning");
            return;
        }

        $path = $this->getAbsolutePath($this->getActualDir());
        $zip = new \Ixtrum\FileManager\Application\Zip($this->system->parameters, $path);
        $zip->addFiles($this->selectedFiles);

        if ($this->system->parameters["cache"]) {
            $this->system->caching->deleteItem(array("content", $path));
        }
    }

    public function handleOrderBy($key)
    {
        $this->system->session->set("order", $key);
        if ($this->system->parameters["cache"]) {
            $this->system->caching->deleteItem(array("content", $this->getAbsolutePath($this->getActualDir())));
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
            if ($this->isPathValid($this->getActualDir(), $file)) {

                $path = $this->getAbsolutePath($this->getActualDir()) . DIRECTORY_SEPARATOR . $file;
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

    public function handleMove($targetDir = null, $filename = null)
    {
        // if sended by AJAX
        if (!$targetDir) {
            $targetDir = $this->presenter->context->httpRequest->getQuery("targetdir");
        }

        if (!$filename) {
            $filename = $this->presenter->context->httpRequest->getQuery("filename");
        }

        if ($this->system->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Read-only mode enabled!"), "warning");
            return;
        }

        if ($targetDir && $filename) {

            $sourcePath = $this->getAbsolutePath($this->getActualDir()) . DIRECTORY_SEPARATOR . $filename;
            $targetPath = $this->getAbsolutePath($targetDir) . DIRECTORY_SEPARATOR . $filename;
            $this->move($sourcePath, $targetPath);
            $this->presenter->payload->result = "success";
        }
    }

    public function handleOpenDir($dir)
    {
        $this->setActualDir($dir);
    }

    public function handleShowThumb($dir, $fileName)
    {
        $thumb = $this->getAbsolutePath($dir) . DIRECTORY_SEPARATOR . $fileName;
        $thumb->send();
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . "/$this->view.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->files = $this->loadData(
                $this->getActualDir(), $this->system->session->get("mask"), $this->view, $this->system->session->get("order")
        );
        $this->template->actualdir = $this->getActualDir();
        $this->template->rootname = $this->system->filesystem->getRootName();
        $this->template->thumb_dir = $this->system->parameters["resDir"] . "img/icons/$this->view/";

        // Load plugins
        if ($this->system->parameters["plugins"]) {

            $this->template->plugins = array();
            foreach ($this->system->parameters["plugins"] as $plugin) {

                if (in_array("context", $plugin["integration"])) {
                    $this->template->plugins[] = $plugin;
                }
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
        $files = Finder::find($mask)
                ->in($this->getAbsolutePath($dir))
                ->orderBy($order);

        $dir_array = array();
        foreach ($files as $file) {

            $name = $file->getFilename();
            $dir_array[$name]["filename"] = $name;
            $dir_array[$name]["modified"] = $file->getMTime();

            if (!is_dir($file->getPath() . "/$name")) {

                $dir_array[$name]["type"] = "file";
                $dir_array[$name]["size"] = $this->system->filesystem->getSize($file->getPathName());
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

            $absDir = $this->getAbsolutePath($dir);
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

    /**
     * Move file/folder
     *
     * @param string $source Source path
     * @param string $target Target path
     *
     * @todo it can be in file manager class, accessible fot other controls
     */
    private function move($source, $target)
    {
        // Validate free space
        if ($this->getFreeSpace() < $this->system->filesystem->getSize($source)) {
            $this->flashMessage($this->system->translator->translate("Disk full, can not continue!", "warning"));
            return;
        }

        // Target folder can not be it's subfolder
        if (is_dir($source) && $this->system->filesystem->isSubFolder($source, $target)) {
            $this->flashMessage($this->system->translator->translate("Target folder is it's subfolder, can not continue!", "warning"));
            return;
        }

        $this->system->filesystem->copy($source, $target);
        if (!$this->system->filesystem->delete($source)) {
            $this->flashMessage($this->system->translator->translate("System was is not able to remove some original files.", "warning"));
        }

        // Remove thumbs
        if (is_dir($source)) {
            $this->system->thumbs->deleteDirThumbs($source);
        } else {
            $this->system->thumbs->deleteThumb($source);
        }

        // Clear cache if needed
        if ($this->system->parameters["cache"]) {

            if (is_dir($source)) {
                $this->system->caching->deleteItemsRecursive($source);
            }
            $this->system->caching->deleteItem(null, array("tags" => "treeview"));
            $this->system->caching->deleteItem(array("content", dirname($source)));
            $this->system->caching->deleteItem(array("content", dirname($target)));
        }

        $this->parent->parent->flashMessage($this->system->translator->translate("Succesfully moved."));
    }

}