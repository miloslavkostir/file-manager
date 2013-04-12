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

use Ixtrum\FileManager\Application\FileSystem;

/**
 * Clipboard control.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class Clipboard extends \Ixtrum\FileManager\Application\Controls
{

    /**
     * Clear clipboard
     */
    public function handleClearClipboard()
    {
        $this->system->session->clear("clipboard");
    }

    /**
     * Paste items from clipboard to actual dir
     *
     * @return void
     */
    public function handlePasteFromClipboard()
    {
        $actualDir = $this->getActualDir();
        if (!$this->isPathValid($actualDir)) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Target directory '%s' is not valid!", $actualDir), "warning");
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

    /**
     * Remove item from clipboard
     *
     * @param string $dir      Directory path
     * @param string $filename File name
     */
    public function handleRemoveFromClipboard($dir, $filename)
    {
        $this->system->session->remove("clipboard", $dir . $filename);
    }

    /**
     * Render control
     */
    public function render()
    {
        $this->template->setFile(__DIR__ . "/Clipboard.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->clipboard = $this->system->session->get("clipboard");
        $this->template->rootname = FileSystem::getRootName();
        $this->template->render();
    }

    /**
     * Move file/dir
     *
     * @param string $source Source path
     * @param string $target Target dir
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

        // Target directory can not be it's sub-directory
        if (is_dir($source) && $this->system->filesystem->isSubDir($source, $target)) {
            $this->flashMessage($this->system->translator->translate("Target directory is it's sub-directory, can not continue!", "warning"));
            return;
        }

        $this->system->filesystem->copy($source, $target);
        if (!$this->system->filesystem->delete($source)) {
            $this->flashMessage($this->system->translator->translate("System is not able to remove some original files.", "warning"));
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

    /**
     * Copy file/dir
     *
     * @param string $source Source file/dir
     * @param string $target Target dir
     *
     * @return void
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

        // Targer directory can not be sub-directory of source directory
        if (is_dir($source) && $this->system->filesystem->isSubDir($source, $target)) {
            $this->parent->parent->flashMessage($this->system->translator->translate("Target directory is it's sub-directory, can not continue!", "warning"));
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