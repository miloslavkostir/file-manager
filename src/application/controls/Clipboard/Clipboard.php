<?php

namespace Ixtrum\FileManager\Application\Controls;

use Ixtrum\FileManager\Application\FileSystem;

class Clipboard extends \Ixtrum\FileManager\Application\Controls
{

    public function handleClearClipboard()
    {
        $this->system->session->clear("clipboard");
    }

    public function handlePasteFromClipboard()
    {
        $actualDir = $this->getActualDir();
        if (!$this->isPathValid($actualDir)) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Target folder '%s' is not valid!", $actualDir), "warning");
            return;
        }

        if ($this->system->parameters["readonly"]) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Read-only mode enabled!"), "warning");
            return;
        }

        foreach ($this->system->session->get("clipboard") as $action) {

            $source = $this->getAbsolutePath($action["actualdir"]) . DIRECTORY_SEPARATOR . $action["filename"];
            if (!file_exists($source)) {
                $this->parent->parent->flashMessage($this->system->translator->translate("Source '%s' already does not exist!", $actualDir), "warning");
                continue;
            }

            $target = $this->getAbsolutePath($actualDir);
            if ($action["action"] === "copy") {
                $this->copy($source, $target);
            }
            if ($action["action"] === "cut") {
                $this->move($source, $target);
            }
        }
        $this->system->session->clear("clipboard");
    }

    public function handleRemoveFromClipboard($dir, $filename)
    {
        $this->system->session->remove("clipboard", $dir . $filename);
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . "/Clipboard.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->clipboard = $this->system->session->get("clipboard");
        $this->template->rootname = FileSystem::getRootName();
        $this->template->render();
    }

    /**
     * Move file/folder
     *
     * @param string $source Source path
     * @param string $target Target folder
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
            $this->flashMessage($this->system->translator->translate("System is not able to remove some original files.", "warning"));
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
            $this->system->caching->deleteItem(array("content", $target));
        }

        $this->parent->parent->flashMessage($this->system->translator->translate("Succesfully moved."));
    }

    /**
     * Copy file/folder
     *
     * @param string $source Source file/folder
     * @param string $target Target folder
     *
     * @todo it can be in file manager class, accessible fot other controls
     */
    private function copy($source, $target)
    {
        // Validate disk size left
        if ($this->getFreeSpace() < $this->system->filesystem->getSize($source)) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Disk full, can not continue!", "warning"));
            return;
        }

        // Targer folder can not be subfolder of source folder
        if (is_dir($source) && $this->system->filesystem->isSubFolder($source, $target)) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Target folder is it's subfolder, can not continue!", "warning"));
            return;
        }

        $this->system->filesystem->copy($source, $target);
        $this->parent->parent->flashMessage($this->system->translator->translate("Succesfully copied."));

        // Clear cache if needed
        if ($this->system->parameters["cache"]) {

            $this->system->caching->deleteItem(array("content", $target));
            $this->system->caching->deleteItem(null, array("tags" => "treeview"));
        }
    }

}