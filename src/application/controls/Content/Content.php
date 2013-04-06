<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

namespace Ixtrum\FileManager\Application\Controls;

use Nette\Application\Responses\FileResponse,
    Ixtrum\FileManager\Application\FileSystem\Finder,
    Ixtrum\FileManager\Application\FileSystem;

/**
 * Content control.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class Content extends \Ixtrum\FileManager\Application\Controls
{

    /**
     * Show file/dir details
     */
    public function handleInfo()
    {
        $this->parent->parent->template->fileinfo = $this->getActualDir();
    }

    /**
     * Copy file/dir
     */
    public function handleCopy()
    {
        foreach ($this->selected as $file) {

            $this->system->session->add(
                    "clipboard", $this->getActualDir() . $file, array(
                "action" => "copy",
                "actualdir" => $this->getActualDir(),
                "filename" => $file
                    )
            );
        }
    }

    /**
     * Cut file/dir
     */
    public function handleCut()
    {
        foreach ($this->selected as $file) {

            $this->system->session->add(
                    "clipboard", $this->getActualDir() . $file, array(
                "action" => "cut",
                "actualdir" => $this->getActualDir(),
                "filename" => $file
                    )
            );
        }
    }

    /**
     * Delete file/dir
     *
     * @return void
     */
    public function handleDelete()
    {
        if ($this->system->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Read-only mode enabled!"), "warning");
            return;
        }

        foreach ($this->selected as $file) {

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

    /**
     * Order files by
     *
     * @param string $key Order key
     */
    public function handleOrderBy($key)
    {
        $this->system->session->set("order", $key);
        if ($this->system->parameters["cache"]) {
            $this->system->caching->deleteItem(array("content", $this->getAbsolutePath($this->getActualDir())));
        }
    }

    /**
     * Run plugin
     *
     * @param string $name Plugin name
     */
    public function handleRunPlugin($name)
    {
        $this->parent->parent->handleRunPlugin($name);
    }

    /**
     * Download file
     *
     * @return void
     */
    public function handleDownload()
    {
        if (count($this->selected) === 1) {

            $file = $this->selected[0];
            if (!$this->isPathValid($this->getActualDir(), $file)) {

                $this->parent->parent->flashMessage($this->system->translator->translate("File %s not found!", $file), "warning");
                return;
            }
            $path = $this->getAbsolutePath($this->getActualDir()) . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {

                $this->parent->parent->flashMessage($this->system->translator->translate("You can download only files, not folders!"), "warning");
                return;
            }
            $this->presenter->sendResponse(new FileResponse($path, $file, null));
        }
    }

    /**
     * Go to parent dir from actual path
     */
    public function handleGoToParent()
    {
        $parent = dirname($this->getActualDir());
        if ($parent == "\\" || $parent == ".") {
            $parentDir = FileSystem::getRootname();
        } else {
            $parentDir = $parent . "/";
        }
        $this->setActualDir($parentDir);
    }

    /**
     * Move file/dir
     *
     * @param string $targetDir Target dir
     * @param string $filename  File name
     *
     * @return void
     */
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
            $this->move($sourcePath, $this->getAbsolutePath($targetDir));
            $this->presenter->payload->result = "success";
        }
    }

    /**
     * Go to to dir
     *
     * @param string $dir Dir
     */
    public function handleOpenDir($dir)
    {
        $this->setActualDir($dir);
    }

    /**
     * Show thumb image
     *
     * @param string $dir      Dir
     * @param string $fileName File name
     */
    public function handleShowThumb($dir, $fileName)
    {
        if ($this->system->parameters["thumbs"]) {
            $this->system->thumbs->getThumbFile($this->getAbsolutePath($dir) . "/$fileName")->send();
        }
    }

    /**
     * Render control
     */
    public function render()
    {
        $this->template->setFile(__DIR__ . "/$this->view.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->files = $this->loadData(
                $this->getActualDir(), $this->system->session->get("mask"), $this->view, $this->system->session->get("order")
        );
        $this->template->actualdir = $this->getActualDir();
        $this->template->rootname = FileSystem::getRootName();
        $this->template->view = $this->view;
        $this->template->resUrl = $this->system->parameters["resUrl"];
        $this->template->resDir = $this->system->parameters["resDir"];
        $this->template->timeFormat = $this->system->translator->getTimeFormat();

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
     * Get directory content
     *
     * @param string $dir   Dir
     * @param string $mask  Mask
     * @param string $view  View
     * @param string $order Order
     *
     * @return array
     *
     * @todo Finder does not support mask for folders
     */
    private function getDirectoryContent($dir, $mask, $view, $order)
    {
        $files = Finder::find($mask)
                ->in($this->getAbsolutePath($dir))
                ->orderBy($order);

        $content = array();
        foreach ($files as $file) {

            $name = $file->getFilename();
            $content[$name]["modified"] = $file->getMTime();
            $content[$name]["dir"] = false;

            if ($file->isFile()) {

                $content[$name]["size"] = $this->system->filesystem->getSize($file->getPathName());
                $content[$name]["extension"] = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));

                $content[$name]["thumb"] = false;
                if ($this->system->parameters["thumbs"]) {
                    $content[$name]["thumb"] = in_array($content[$name]["extension"], $this->system->thumbs->supported);
                }
            } else {
                $content[$name]["dir"] = true;
            }
        }
        return $content;
    }

    /**
     * Load data from actual directory
     *
     * @param string $dir   Dir
     * @param string $mask  Mask
     * @param string $view  View
     * @param string $order Order
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
     * Move file/dir
     *
     * @param string $source Source path
     * @param string $target Target dir
     *
     * @return void
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
        if ($this->system->parameters["thumbs"]) {

            if (is_dir($source)) {
                $this->system->thumbs->deleteDirThumbs($source);
            } else {
                $this->system->thumbs->deleteThumb($source);
            }
        }

        // Clear cache if needed
        if ($this->system->parameters["cache"]) {

            if (is_dir($source)) {
                $this->system->caching->deleteItemsRecursive($source);
            }
            $this->system->caching->deleteItem(null, array("tags" => "treeview"));
            $this->system->caching->deleteItem(array("content", dirname($source)));
            $this->system->caching->deleteItem(array("content", $target));
        }

        $this->parent->parent->flashMessage($this->system->translator->translate("Succesfully moved."));
    }

}