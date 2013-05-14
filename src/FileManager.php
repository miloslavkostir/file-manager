<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

namespace Ixtrum;

use Ixtrum\FileManager\FileSystem,
    Nette\Application\Responses\FileResponse,
    Nette\Application\UI,
    Nette\Utils\Html,
    Nette\Http;

/**
 * File Manager base class.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class FileManager extends UI\Control
{

    const NAME = "iXtrum File Manager";
    const VERSION = "dev-master";

    /** @var \Ixtrum\FileManager\Loader */
    protected $system;

    /** @var array */
    protected $selectedFiles = array();

    /** @var string */
    protected $view = "large";

    /** @var array */
    private $views = array("details", "large", "list", "small");

    /** @var \Nette\Http\Request */
    private $httpRequest;

    /**
     * Constructor
     *
     * @param \Nette\Http\Request $request HTTP request
     * @param \Nette\Http\Session $session Session
     * @param array               $config  Custom configuration
     */
    public function __construct(Http\Request $request, Http\Session $session, $config = array())
    {
        parent::__construct();

        $this->httpRequest = $request;

        // Create system container with services and configuration
        $this->system = new FileManager\Loader($session, $config);
        $this->system->freeze();

        // Get & validate actual dir
        $actualDir = $this->system->session->actualdir;
        $actualPath = $this->getAbsolutePath($actualDir);
        if (!is_dir($actualPath)) {
            // Set root directory as default
            $actualDir = FileSystem::getRootname();
        }
        $this->setActualDir($actualDir);

        // Get selected files via POST
        $selectedFiles = $request->getPost("files");
        if (is_array($selectedFiles)) {
            $this->selectedFiles = $selectedFiles;
        }

        // Get & validate selected view
        $view = $this->system->session->view;
        if (in_array($view, $this->views)) {
            $this->view = $view;
        }
        \nette\diagnostics\firelogger::log($this->system->session->clipboard);
        $this->invalidateControl();
    }

    /**
     * Delete file/dir
     *
     * @return void
     */
    public function handleDelete()
    {
        if ($this->system->parameters["readonly"]) {
            $this->flashMessage($this->system->translator->translate("Read-only mode enabled!"), "warning");
            return;
        }

        foreach ($this->selectedFiles as $file) {

            $path = $this->getAbsolutePath($this->getActualDir()) . DIRECTORY_SEPARATOR . $file;
            if (!file_exists($path)) {
                $this->flashMessage($this->system->translator->translate("'%s' already does not exist!", $file), "warning");
                continue;
            }

            if (!$this->system->filesystem->delete($path)) {
                $this->flashMessage($this->system->translator->translate("It's not possible to delete '%s'!", $file), "warning");
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

            $this->flashMessage($this->system->translator->translate("'%s' successfuly deleted.", $file));
        }
    }

    /**
     * Download file
     *
     * @return void
     */
    public function handleDownload()
    {
        $actualDir = $this->presenter->getParameter("actualDir");
        $filename = $this->presenter->getParameter("filename");

        if (!$this->isPathValid($actualDir, $filename)) {

            $this->flashMessage($this->system->translator->translate("File %s not found!", $filename), "warning");
            return;
        }
        $path = $this->getAbsolutePath($actualDir) . DIRECTORY_SEPARATOR . $filename;
        if (is_dir($path)) {

            $this->flashMessage($this->system->translator->translate("You can download only files, not directories!"), "warning");
            return;
        }
        $this->presenter->sendResponse(new FileResponse($path, $filename, null));
    }

    /**
     * Go to parent directory from actual path
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
     * Go to to dir
     *
     * @param string $dir Directory
     */
    public function handleOpenDir($dir)
    {
        $this->setActualDir($dir);
    }

    /**
     * Show thumb image
     *
     * @param string $dir      Directory
     * @param string $fileName File name
     */
    public function handleShowThumb($dir, $fileName)
    {
        if ($this->system->parameters["thumbs"]) {
            $this->system->thumbs->getThumbFile($this->getAbsolutePath($dir) . "/$fileName")->send();
        }
    }

    /**
     * Order files by
     *
     * @param string $key Order key
     */
    public function handleOrderBy($key)
    {
        $this->system->session->order = $key;
        if ($this->system->parameters["cache"]) {
            $this->system->caching->deleteItem(array("content", $this->getAbsolutePath($this->getActualDir())));
        }
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
            $targetDir = $this->httpRequest->getQuery("targetdir");
        }

        if (!$filename) {
            $filename = $this->httpRequest->getQuery("filename");
        }

        if ($this->system->parameters["readonly"]) {
            $this->flashMessage($this->system->translator->translate("Read-only mode enabled!"), "warning");
            return;
        }

        if ($targetDir && $filename) {

            $sourcePath = $this->getAbsolutePath($this->getActualDir()) . DIRECTORY_SEPARATOR . $filename;
            $this->move($sourcePath, $this->getAbsolutePath($targetDir));
            $this->presenter->payload->result = "success";
        }
    }

    /**
     * Run plugin in content
     *
     * @param string $pluginName Plugin name
     */
    public function handleRunContentPlugin($pluginName)
    {
        // Find valid plugin
        foreach ($this->system->parameters["plugins"] as $name => $config) {

            if ($pluginName === $name) {

                if (!isset($config["types"]["content"])) {
                    continue;
                }
                $this->template->plugin = $pluginName;
            }
        }

        if (!isset($this->template->plugin)) {
            $this->flashMessage($this->system->translator->translate("Plugin '%s' not found!", $name), "warning");
        }
    }

    /**
     * Getter for actualDir
     *
     * @return string Actual directory
     */
    public function getActualDir()
    {
        return $this->system->session->actualdir;
    }

    /**
     * Set actual dir
     *
     * @param string $dir Relative directory path
     */
    public function setActualDir($dir)
    {
        if ($this->isPathValid($dir)) {
            $this->system->session->actualdir = $dir;
        }
    }

    /**
     * Render all
     */
    public function render()
    {
        $this->renderCss();
        $this->renderAddressbar();
        $this->renderToolbar();
        $this->renderBody();
        $this->renderInfobar();
        $this->renderScripts();
        $this->renderContextMenu();
    }

    /**
     * Render addressbar
     */
    public function renderAddressbar()
    {
        $this->template->setFile(__DIR__ . "/templates/addressbar.latte");
        $this->template->plugins = array();
        foreach ($this->system->parameters["plugins"] as $name => $config) {

            if (isset($config["types"]["addressbar"])) {
                $this->template->plugins[$name] = $config;
            }
        }
        $this->template->render();
    }

    /**
     * Render body
     */
    public function renderBody()
    {
        $this->template->setFile(__DIR__ . "/templates/body.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->files = $this->loadData(
                $this->getActualDir(), $this->system->session->mask, $this->view, $this->system->session->order
        );
        $this->template->treeview = $this->getTreeview();
        $this->template->actualdir = $this->getActualDir();
        $this->template->rootname = FileSystem::getRootName();
        $this->template->view = $this->view;
        $this->template->resUrl = $this->system->parameters["resUrl"];
        $this->template->resDir = $this->system->parameters["resDir"];
        $this->template->timeFormat = $this->system->translator->getTimeFormat();
        $this->template->plugins = array();
        foreach ($this->system->parameters["plugins"] as $name => $config) {

            if (isset($config["types"]["content"])) {
                $this->template->plugins[$name] = $config;
            }
        }
        $this->template->render();
    }

    public function renderContextMenu()
    {
        $this->template->setFile(__DIR__ . "/templates/contextmenu.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->plugins = array();
        foreach ($this->system->parameters["plugins"] as $name => $config) {

            if (isset($config["types"]["contextmenu"])) {
                $this->template->plugins[$name] = $config;
            }
        }
        $this->template->render();
    }

    /**
     * Render css
     */
    public function renderCss()
    {
        $this->template->setFile(__DIR__ . "/templates/css.latte");
        $this->template->resUrl = $this->system->parameters["resUrl"];

        // Get theme
        $theme = $this->system->session->theme;
        if ($theme) {
            $this->template->theme = $theme;
        } else {
            $this->template->theme = "default";
        }
        $this->template->render();
    }

    /**
     * Render infobar
     */
    public function renderInfobar()
    {
        $this->template->setFile(__DIR__ . "/templates/infobar.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->plugins = array();
        foreach ($this->system->parameters["plugins"] as $name => $config) {

            if (isset($config["types"]["infobar"])) {
                $this->template->plugins[$name] = $config;
            }
        }
        $this->template->render();
    }

    /**
     * Render scripts
     */
    public function renderScripts()
    {
        $this->template->setFile(__DIR__ . "/templates/scripts.latte");
        $this->template->resUrl = $this->system->parameters["resUrl"];
        $this->template->render();
    }

    /**
     * Render toolbar
     */
    public function renderToolbar()
    {
        $this->template->setFile(__DIR__ . "/templates/toolbar.latte");
        $this->template->setTranslator($this->system->translator);

        // Sort messages according to priorities - 1. error, 2. warning, 3. info
        usort($this->template->flashes, function($next, $current) {

                    if ($current->type === "warning" && $next->type === "info" || $current->type === "error" && $next->type !== "error"
                    ) {
                        return +1;
                    }
                });

        $this->template->plugins = array();
        foreach ($this->system->parameters["plugins"] as $name => $config) {

            if (isset($config["types"]["toolbar"])) {
                $this->template->plugins[$name] = $config;
            }
        }
        $this->template->render();
    }

    /**
     * Callback for error event in form
     *
     * @param \Nette\Application\UI\Form $form Form instance
     *
     * @return void
     */
    public function onFormError(UI\Form $form)
    {
        foreach ($form->errors as $error) {
            $this->flashMessage($error, "warning");
        }
    }

    /**
     * Plugin component factory
     *
     * @return \Nette\Application\UI\Multiplier
     */
    protected function createComponentPlugin()
    {
        $system = $this->system;
        $selectedFiles = $this->selectedFiles;
        $view = $this->view;

        return new UI\Multiplier(function ($name) use ($system, $selectedFiles, $view) {

                    $class = $system->parameters["plugins"][$name]["class"];
                    return new $class($name, $system, $selectedFiles, $view);
                });
    }

    /**
     * Get free space available
     *
     * @return integer
     */
    public function getFreeSpace()
    {
        if ($this->system->parameters["quota"]) {
            return $this->system->parameters["quotaLimit"] * 1048576 - $this->system->filesystem->getSize($this->system->parameters["dataDir"]);
        } else {
            return disk_free_space($this->system->parameters["dataDir"]);
        }
    }

    /**
     * Path validator
     *
     * @param string $dir  Dirname as relative path
     * @param string $file Filename (optional)
     *
     * @return boolean
     */
    public function isPathValid($dir, $file = null)
    {
        $path = $this->getAbsolutePath($dir);
        if ($file) {
            $path .= DIRECTORY_SEPARATOR . $file;
        }

        if (!file_exists($path)) {
            return false;
        }

        if ($this->system->parameters["dataDir"] === $path) {
            return true;
        }

        return $this->system->filesystem->isSubDir($this->system->parameters["dataDir"], $path);
    }

    /**
     * Get absolute path from relative path
     *
     * @param string $actualDir Actual directory as relative path
     *
     * @return string
     */
    public function getAbsolutePath($actualDir)
    {
        return realpath($this->system->parameters["dataDir"] . $actualDir);
    }

    /**
     * Get directory content
     *
     * @param string $dir   Directory
     * @param string $mask  Mask
     * @param string $order Order
     *
     * @return array
     *
     * @todo Finder does not support mask for directories
     */
    private function getDirectoryContent($dir, $mask, $order)
    {
        $files = FileSystem\Finder::find($mask)
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
     * @param string $dir   Directory
     * @param string $mask  Mask
     * @param string $order Order
     *
     * @return array
     */
    private function loadData($dir, $mask, $order)
    {
        // Default filter mask
        if (empty($mask)) {
            $mask = "*";
        }

        if ($this->system->parameters["cache"] && $mask === "*") {

            $absDir = $this->getAbsolutePath($dir);

            $cacheData = $this->system->caching->getItem(array("content", $absDir));
            if ($cacheData) {
                return $cacheData;
            }

            $output = $this->getDirectoryContent($dir, $mask, $order);
            $this->system->caching->saveItem(array("content", $absDir), $output);
            return $output;
        }

        return $this->getDirectoryContent($dir, $mask, $order);
    }

    /**
     * Move file/dir
     *
     * @param string $source Source path
     * @param string $target Target dir
     *
     * @return void
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

        $this->flashMessage($this->system->translator->translate("Succesfully moved."));
    }

    /**
     * Generate directory treeview
     *
     * @param string $dir      Path to root dir
     * @param string $superior Superior dir
     *
     * @return \Nette\Utils\Html
     *
     * @throws \Exception
     */
    private function generateTreeview($dir, $superior = null)
    {
        if (!is_dir($dir)) {
            throw new \Exception("Directory '$dir' does not exist!");
        }

        $list = Html::el("ul");
        foreach (FileSystem\Finder::findDirectories("*")->in($dir) as $dir) {

            $path = "$superior/" . $dir->getFileName();

            // Create file/dir link
            $link = Html::el("a")->href($this->link("openDir!", "$path/"))->class("ajax");
            $link[0] = Html::el("i")->class("icon-folder-open");
            $link[1] = Html::el("span", $dir->getFileName());

            // Create item in list
            $item = Html::el("li");
            $item[0] = $link;
            $item[1] = $this->generateTreeview($dir->getPathName(), $path);

            $list->add($item);
        }
        return $list;
    }

    /**
     * Get treeview
     *
     * @return string
     */
    private function getTreeview()
    {
        if ($this->system->parameters["cache"]) {

            $path = $this->system->parameters["dataDir"];
            $cacheData = $this->system->caching->getItem($path);
            if (!$cacheData) {

                $output = $this->generateTreeview($this->system->parameters["dataDir"]);
                $this->system->caching->saveItem($path, $output, array("tags" => array("treeview")));
                return $output;
            } else {
                return $cacheData;
            }
        } else {
            return $this->generateTreeview($this->system->parameters["dataDir"]);
        }
    }

}