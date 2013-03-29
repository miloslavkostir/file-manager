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

use Ixtrum\FileManager\Application\FileSystem,
    Ixtrum\FileManager\Application\FileSystem\Finder,
    Nette\DI\Container,
    Nette\Application\UI\Form,
    Nette\Application\UI\Control,
    Nette\Application\UI\Multiplier;

/**
 * File Manager base class.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class FileManager extends Control
{

    const NAME = "iXtrum File Manager";
    const VERSION = "dev-master";

    /** @var \Ixtrum\FileManager\Application\Loader */
    protected $system;

    /** @var array */
    protected $selectedFiles = array();

    /**
     * Constructor
     *
     * @param \Nette\DI\Container $container System container
     * @param array               $config    User configuration
     */
    public function __construct(Container $container, $config = array())
    {
        parent::__construct();

        // Add important base parameters to config
        $config["wwwDir"] = $container->parameters["wwwDir"];
        $config["tempDir"] = $container->parameters["tempDir"];

        // Load system container with services and configuration
        $this->system = new FileManager\Application\Loader($container->session, $config);
        $this->system->freeze();

        // Get & validate actual dir
        $actualDir = $this->system->session->get("actualdir");
        $actualPath = $this->getAbsolutePath($actualDir);
        if (!is_dir($actualPath) || empty($actualDir)) {
            // Set root dir as default
            $actualDir = FileSystem::getRootname();
        }
        $this->setActualDir($actualDir);

        // Get selected files via POST
        $selectedFiles = $container->httpRequest->getPost("files");
        if (is_array($selectedFiles)) {
            $this->selectedFiles = $selectedFiles;
        }

        $this->invalidateControl();
    }

    /**
     * Show new folder control
     */
    public function handleNewFolder()
    {
        $this->template->newFolder = true;
    }

    /**
     * Show rename control
     */
    public function handleRename()
    {
        $this->template->rename = true;
    }

    /**
     * Refresh content - clear cache
     */
    public function handleRefreshContent()
    {
        if ($this->system->parameters["cache"]) {

            $this->system->caching->deleteItem(null, array("tags" => "treeview"));
            $this->system->caching->deleteItem(array(
                "content",
                $this->getAbsolutePath($this->getActualDir())
            ));
        }
    }

    /**
     * Run plugin
     *
     * @param string $name Plugin name
     */
    public function handleRunPlugin($name)
    {
        // Find valid plugin
        foreach ($this->system->parameters["plugins"] as $plugin) {
            if ($name === $plugin["name"]) {
                $validPlugin = true;
            }
        }

        if (isset($validPlugin)) {
            $this->template->plugin = $name;
        } else {
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
        return $this->system->session->get("actualdir");
    }

    /**
     * Get available languages
     *
     * @return array
     */
    public static function getLanguages()
    {
        $config = self::getDefaults();
        $languages = array($config["lang"] => $config["lang"]);
        $files = Finder::findFiles("*.json")->in($config["langDir"]);
        foreach ($files as $file) {

            $baseName = $file->getBasename(".json");
            $languages[$baseName] = $baseName;
        }
        return $languages;
    }

    /**
     * Set actual dir
     *
     * @param string $dir relative dir path
     */
    public function setActualDir($dir)
    {
        if ($this->isPathValid($dir)) {
            $this->system->session->set("actualdir", $dir);
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
        $this->renderMessages();
        $this->renderContent();
        $this->renderInfobar();
        $this->renderScripts();
    }

    /**
     * Render addressbar
     */
    public function renderAddressbar()
    {
        $this->template->setFile(__DIR__ . "/templates/addressbar.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->render();
    }

    /**
     * Render content
     */
    public function renderContent()
    {
        $this->template->setFile(__DIR__ . "/templates/content.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->render();
    }

    /**
     * Render css
     */
    public function renderCss()
    {
        $this->template->setFile(__DIR__ . "/templates/css.latte");
        $this->template->resDir = $this->system->parameters["resDir"];

        // Get theme
        $theme = $this->system->session->get("theme");
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

        // Get plugins
        $this->template->fileInfoPlugins = array();
        foreach ($this->system->parameters["plugins"] as $plugin) {

            if (in_array("fileinfo", $plugin["integration"])) {
                $this->template->fileInfoPlugins[] = $plugin;
            }
        }
        $this->template->render();
    }

    /**
     * Render messages
     */
    public function renderMessages()
    {
        $this->template->setFile(__DIR__ . "/templates/messages.latte");
        $this->template->setTranslator($this->system->translator);

        // Sort messages according to priorities - 1. error, 2. warning, 3. info
        usort($this->template->flashes, function($next, $current) {

                    if ($current->type === "warning" && $next->type === "info" || $current->type === "error" && $next->type !== "error") {
                        return +1;
                    }
                });

        $this->template->render();
    }

    /**
     * Render scripts
     */
    public function renderScripts()
    {
        $this->template->setFile(__DIR__ . "/templates/scripts.latte");
        $this->template->resDir = $this->system->parameters["resDir"];
        $this->template->render();
    }

    /**
     * Render toolbar
     */
    public function renderToolbar()
    {
        $this->template->setFile(__DIR__ . "/templates/toolbar.latte");
        $this->template->setTranslator($this->system->translator);
        $this->template->toolbarPlugins = array();
        foreach ($this->system->parameters["plugins"] as $plugin) {

            if (in_array("toolbar", $plugin["integration"])) {
                $this->template->toolbarPlugins[] = $plugin;
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
    public function onFormError(Form $form)
    {
        foreach ($form->errors as $error) {
            $this->flashMessage($error, "warning");
        }
    }

    /**
     * Control component factory
     *
     * @return \Nette\Application\UI\Multiplier
     */
    protected function createComponentControl()
    {
        $system = $this->system;
        $selectedFiles = $this->selectedFiles;
        return new Multiplier(function ($name) use ($system, $selectedFiles) {
                    $namespace = __NAMESPACE__;
                    $namespace .= "\\FileManager\Application\Controls";
                    $class = "$namespace\\$name";
                    return new $class($system, $selectedFiles);
                });
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
        return new Multiplier(function ($name) use ($system, $selectedFiles) {

                    $class = $system->parameters["plugins"][$name]["class"];
                    return new $class($name, $system, $selectedFiles);
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
            return $this->system->parameters["quotaLimit"] * 1048576 - $this->system->filesystem->getSize($this->system->parameters["uploadroot"]);
        } else {
            return disk_free_space($this->system->parameters["uploadroot"]);
        }
    }

    /**
     * Get default parameters
     *
     * @return array
     */
    public static function getDefaults()
    {
        return array(
            "uploadroot" => null,
            "cache" => true,
            "cacheStorage" => "FileStorage",
            "readonly" => false,
            "quota" => false,
            "quotaLimit" => 20, // megabytes
            "lang" => "en",
            "tempDir" => null,
            "wwwDir" => null,
            "resDir" => "ixtrum-res",
            "pluginDir" => __DIR__ . "/plugins",
            "langDir" => __DIR__ . "/lang"
        );
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

        if ($this->system->parameters["uploadroot"] === $path) {
            return true;
        }

        return $this->system->filesystem->isSubFolder($this->system->parameters["uploadroot"], $path);
    }

    /**
     * Get absolute path from relative path
     *
     * @param string $actualdir Actual dir in relative format
     *
     * @return string
     */
    public function getAbsolutePath($actualdir)
    {
        return realpath($this->system->parameters["uploadroot"] . $actualdir);
    }

}