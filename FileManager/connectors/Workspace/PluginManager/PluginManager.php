<?php

use Nette\Utils\Json,
    Ixtrum\FileManager\Application\FileSystem,
    Ixtrum\FileManager\Application\FileSystem\Finder,
    Nette\Application\UI\Form;

class Plugins extends Nette\Application\UI\Control
{

    /** @var string */
    private $pluginDir;

    /**
     * Constructor
     *
     * @param string $pluginDir
     */
    public function __construct($pluginDir)
    {
        if (!is_dir($pluginDir)) {
            mkdir($pluginDir);
        }
        $this->pluginDir;
    }

    /**
     * Install plugin handler
     *
     * @param string $filename Plugin file name
     *
     * @return void
     */
    public function handleInstall($filename)
    {
        if (empty($filename)) {
            $this->presenter->flashMessage("No plugin name selected!", "warning");
            return;
        }

        $path = $this->pluginDir . DIRECTORY_SEPARATOR . $filename;
        if (!file_exists($path)) {
            $this->presenter->flashMessage("Plugin '$filename' not found!", "warning");
            return;
        }

        // @todo plugin validator

        $config = $this->getPluginConfig($path);
        $name = $config["name"];

        $parameters = $this->getComponent("fileManager")->getSystemParameters();
        if (isset($parameters["plugins"][$name])) {
            $this->presenter->flashMessage("Plugin '$name' already installed!", "warning");
            return;
        }

        // Create plugin dir
        $targetDir = $parameters["pluginDir"] . DIRECTORY_SEPARATOR . $name;
        $fileSystem = new FileSystem;
        if (is_dir($targetDir)) {
            $fileSystem->delete($targetDir);
        } else {
            $fileSystem->mkdir($targetDir);
        }

        // Extract ZIP
        $this->plugins->extractZip($path, $targetDir);

        // Synchronize resources
        $this->getComponent("fileManager")->syncResources();

        $this->presenter->flashMessage("Plugin '$name' successfully installed.");
    }

    /**
     * Shows plugin form in template
     */
    public function handleShowPluginForm()
    {
        $this->template->show = "pluginForm";
    }

    /**
     * Uninstall plugin handler
     *
     * @param string $name Plugin name
     *
     * @return void
     */
    public function handleUninstall($name)
    {
        $parameters = $this->getComponent("fileManager")->getSystemParameters();
        if (!isset($parameters["plugins"][$name])) {
            $this->presenter->flashMessage("Plugin '$name' not found!", "warning");
            return;
        }

        $path = $parameters["plugins"][$name]["path"];
        $fileSystem = new FileSystem;
        $fileSystem->delete($path);
        $this->presenter->flashMessage("Plugin '$name' successfully uninstalled.", "warning");

        if (!$this->isAjax()) {
            $this->redirect("this");
        }
    }

    /**
     * Delete plugin
     *
     * @param string $filename File name
     *
     * @return void
     */
    public function handleDelete($filename)
    {
        $path = $this->pluginDir . DIRECTORY_SEPARATOR . $filename;
        if (!file_exists($path)) {
            $this->presenter->flashMessage("File '$filename' not found!", "warning");
            return;
        }
        if (unlink($path)) {
            $this->presenter->flashMessage("Plugin '$filename' successfully deleted.");
        }
    }

    /**
     * Default render method
     */
    public function render()
    {
        $parameters = $this->getComponent("fileManager")->getSystemParameters(); // @todo
        $this->template->installed = $parameters["plugins"];
        $this->template->warnings = array();
        $this->template->plugins = array();

        foreach (Finder::findFiles("*.zip")->in($this->pluginDir) as $plugin) {

            $config = $this->plugins->getPluginConfig($plugin->getRealPath());
            $filename = $plugin->getFilename();

            // Detect invalid plugins
            if ($config === false) {
                $this->template->warnings[$filename] = "Invalid plugin '$filename', no plugin.json found!";
                continue;
            }

            $this->template->plugins[$filename] = $config;
        }
    }

    /**
     * PluginForm component factory
     *
     * @return \Nette\Application\UI\Form
     */
    public function createComponentPluginForm()
    {
        $form = new Form;

        $upload = $form->addContainer("upload");
        $upload->addUpload("file", "File")
                ->addCondition($form::FILLED)
                ->addRule($form::MAX_FILE_SIZE, "Maximum file size is {$this->getMaxUploadSize()} MB.", $this->getMaxUploadSize());

        $github = $form->addContainer("github");
        $github->addText("repository", "Repository URL");
        $github->addText("branch", "Branch/tag")
                ->setDefaultValue("master");

        $form->addSubmit("submit", "Add");
        $form->onSuccess[] = $this->successPluginForm;
        return $form;
    }

    /**
     * PluginForm success event
     *
     * @param \Nette\Application\UI\Form $form
     */
    public function successPluginForm(Form $form)
    {
        // Upload file
        if (!empty($form->values->upload->file->name)) {

            if (!$form->values->upload->file->isOk()) {

                $this->presenter->flashMessage("File {$form->values->upload->file->name} was not uploaded. Error code {$form->values->upload->file->error}", "error");
                return;
            }

            $targetFile = FileSystem::getUniquePath($this->pluginDir . DIRECTORY_SEPARATOR . $form->values->upload->file->name);
            $form->values->file->move($targetFile);
            $this->presenter->flashMessage("New plugin '" . basename($targetFile) . "' successfully uploaded.");
        }

        // Download repository from Github
        if ($form->values->github->repository && $form->values->github->branch) {

            $url = "{$form->values->github->repository}/archive/{$form->values->github->branch}.zip";
            $targetFile = FileSystem::getUniquePath($this->pluginDir . DIRECTORY_SEPARATOR . basename($url));

            $download = $this->downloadRemoteFile($url, $targetFile);
            if ($download === true) {
                $this->presenter->flashMessage("New plugin '" . basename($targetFile) . "' successfully downloaded from Github.");
            } else {
                $this->presenter->flashMessage("Can not download '$url' from Github - $download!", "error");
                return;
            }
        }

        $this->redirect("this");
    }

    /**
     * Get real maximum upload size limit
     *
     * @return integer Bytes number
     */
    private function getMaxUploadSize()
    {
        $maxUpload = (int) (ini_get("upload_max_filesize"));
        $maxPost = (int) (ini_get("post_max_size"));
        $memoryLimit = (int) (ini_get("memory_limit"));
        return min($maxUpload, $maxPost, $memoryLimit) * 1048576;
    }

    /**
     * Extract files from ZIP
     *
     * @param string $source Source file
     * @param string $target Target folder
     *
     * @return boolean
     */
    private function extractZip($source, $target)
    {
        $zip = new \ZipArchive;
        if ($zip->open($source) === true) {
            $zip->extractTo($target);
            $zip->close();
            return true;
        }
        return false;
    }

    /**
     * Get first occurrence of plugin.json content from ZIP archive as array
     *
     * @param string $path File path
     *
     * @return false|array
     */
    private function getPluginConfig($path)
    {
        $zip = new \ZipArchive;
        if ($zip->open($path) === true) {

            for ($i = 0; $i < $zip->numFiles; $i++) {

                $filename = basename($zip->getNameIndex($i));
                if (strtolower($filename) === "plugin.json") {
                    $config = $zip->getFromIndex($i);
                    break;
                }
            }
            $zip->close();
        } else {
            return false;
        }

        if ($config === false) {
            return false;
        }

        return Json::decode($config, 1);
    }

    /**
     * Download a large distant file to a local destination.
     *
     * This method is very memory efficient :-)
     * The file can be huge, PHP doesn't load it in memory.
     *
     * /!\ Warning, the return value is always true, you must use === to test the response type too.
     *
     * @author dalexandre
     *
     * @link https://gist.github.com/1258787
     *
     * @param string $url  The file to download
     * @param string $dest The local file path or resource (file handler)
     *
     * @return boolean true or the error message
     */
    private function downloadRemoteFile($url, $dest)
    {
        $options = array(
            CURLOPT_FILE => is_resource($dest) ? $dest : fopen($dest, "w"),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_URL => $url,
            CURLOPT_FAILONERROR => true, // HTTP code > 400 will throw curl error
            CURLOPT_SSL_VERIFYPEER => false, // Disable SSL verification fot https links
            CURLOPT_SSL_VERIFYHOST => false // Disable SSL verification fot https links
        );

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $return = curl_exec($ch);

        if ($return === false) {
            return curl_error($ch);
        }
        return true;
    }

    /**
     * Check if given URL exists
     *
     * @link http://stackoverflow.com/questions/2280394/check-if-an-url-exists-in-php
     *
     * @param string $url URL
     *
     * @return boolean
     */
    private function urlExist($url)
    {
        if (!$fp = curl_init($url)) {
            return false;
        }
        return true;
    }

}