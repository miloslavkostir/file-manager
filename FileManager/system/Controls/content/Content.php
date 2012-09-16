<?php

namespace Ixtrum\FileManager\Controls;

use Nette\Application\Responses\FileResponse;

class Content extends \Ixtrum\FileManager
{

    public function handleInfo()
    {
        $this->parent->parent->template->fileinfo = $this->actualDir;
    }

    public function handleCopy()
    {
        foreach ($this->selectedFiles as $file) {

            $this->context->session->add(
                    "clipboard",
                    $this->actualDir . $file,
                    array(
                        "action" => "copy",
                        "actualdir" => $this->actualDir,
                        "filename" => $file
                    )
            );
        }
    }

    public function handleCut()
    {
        foreach ($this->selectedFiles as $file) {

            $this->context->session->add(
                    "clipboard",
                    $this->actualDir . $file,
                    array(
                        "action" => "cut",
                        "actualdir" => $this->actualDir,
                        "filename" => $file
                    )
            );
        }
    }

    public function handleDelete()
    {
        if ($this->context->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->context->translator->translate("Read-only mode enabled!"), "warning");
        } else {

            foreach ($this->selectedFiles as $file) {

                if ($this->context->filesystem->delete($this->actualDir, $file)) {
                    $this->parent->parent->flashMessage($this->context->translator->translate("Successfuly deleted - %s", $file), "info");
                } else {
                    $this->parent->parent->flashMessage($this->context->translator->translate("An error occured - %s", $file), "error");
                }
            }
            $this->handleShowContent($this->actualDir);
        }
    }

    public function handleZip()
    {
        if ($this->context->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->context->translator->translate("Read-only mode enabled!"), "warning");
        } else {

            $actualPath = $this->context->filesystem->getAbsolutePath($this->actualDir);
            $zip = new \Ixtrum\FileManager\Application\Zip($this->context->parameters, $actualPath);
            $zip->addFiles($this->selectedFiles);

            $key = $this->context->filesystem->getRealPath($actualPath);
            if ($this->context->parameters["cache"]) {
                $this->parent->parent->context->caching->deleteItem(array("content", $key));
            }
        }
        $this->handleShowContent($this->actualDir);
    }

    public function handleOrderBy($key)
    {
        $this->context->session->set("order", $key);

        $absPath = $this->context->filesystem->getRealPath($this->context->filesystem->getAbsolutePath($this->actualDir));
        if ($this->context->parameters["cache"]) {
            $this->context->caching->deleteItem(array("content", $absPath));
        }

        $this->handleShowContent($this->actualDir);
    }

    public function handleRunPlugin($name)
    {
        $this->parent->parent->handleRunPlugin($name);
    }

    public function handleDownload()
    {
        if (count($this->selectedFiles) === 1) {

            $file = $this->selectedFiles[0];
            if ($this->context->filesystem->validPath($this->actualDir, $file)) {

                $path = $this->context->filesystem->getAbsolutePath($this->actualDir) . $file;
                $this->presenter->sendResponse(new FileResponse($path, $file, null));
            } else {
                $this->parent->parent->flashMessage($this->context->translator->translate("File %s not found!", $file), "warning");
            }
        }
    }

    public function handleGoToParent()
    {
        $parent = dirname($this->actualDir);
        if ($parent == "\\" || $parent == ".") {
            $parentPath = $this->context->filesystem->getRootname();
        } else {
            $parentPath = $parent . "/";
        }
        $this->handleShowContent($parentPath);
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

        if ($this->context->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->context->translator->translate("Read-only mode enabled!"), "warning");
        } else {

            if ($targetdir && $filename) {

                if ($this->context->filesystem->move($this->actualDir, $targetdir, $filename)) {

                    $this->presenter->payload->result = "success";
                    $this->parent->parent->flashMessage($this->context->translator->translate("Successfuly moved - %s", $filename), "info");
                } else {
                    $this->parent->parent->flashMessage($this->context->translator->translate("An error occured. File %s was not moved.", $filename), "error");
                }
            }

            $this->handleShowContent($this->actualDir);
        }
    }

    public function handleShowContent($dir)
    {
        $this->parent->parent->handleShowContent($dir);
    }

    public function handleShowThumb($dir, $file)
    {
        $path = $this->context->filesystem->getAbsolutePath($dir) . $file;
        $thumb = $this->context->thumbs->getThumbFile($path);
        $thumb->send();
    }

    public function render()
    {
        $mask = $this->context->session->get("mask");
        $order = $this->context->session->get("order");

        if (empty($mask)) {
            $mask = "*";
        }

        if (empty($order)) {
            $order = "type";
        }

        $this->template->setFile(__DIR__ . "/$this->view.latte");
        $this->template->setTranslator($this->context->translator);
        $this->template->files = $this->loadData($this->actualDir, $mask, $this->view, $order);
        $this->template->actualdir = $this->actualDir;
        $this->template->rootname = $this->context->filesystem->getRootName();
        $this->template->thumb_dir = $this->context->parameters["resDir"] . "img/icons/$this->view/";

        // Load plugins
        if ($this->context->parameters["plugins"]) {

            $contextPlugins = array();
            foreach ($this->context->parameters["plugins"] as $plugin) {

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
        $supportedThumbs = $this->context->thumbs->supported;
        $absolutePath = $this->context->filesystem->getAbsolutePath($dir);

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
     * @param string $dir
     * @param string $mask
     * @param string $view
     * @param string $order
     * @return array
     */
    private function loadData($dir, $mask, $view, $order)
    {
        if ($this->context->parameters["cache"]) {

            $absDir = $this->context->filesystem->getRealPath($this->context->filesystem->getAbsolutePath($dir));
            $cacheData = $this->context->caching->getItem(array("content", $absDir));

            if (!$cacheData) {

                $output = $this->getDirectoryContent($dir, $mask, $view, $order);
                $this->context->caching->saveItem(array("content", $absDir), $output);
                return $output;
            } else {
                return $cacheData;
            }
        } else {
            return $this->getDirectoryContent($dir, $mask, $view, $order);
        }
    }

}