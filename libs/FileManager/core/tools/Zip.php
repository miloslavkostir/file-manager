<?php

use Nette\Utils\Finder;

class Zip extends FileManager
{
    /** @var string */
    protected $tempDir;

    /** @var integer */
    protected $expiration = 600;

    function __construct()
    {
        $tempDir = $this->presenter->context->params['tempDir'] . '/cache/file-manager/downloads';

        if (!file_exists($tempDir)) {
                $oldumask = umask(0);
                if (!mkdir($tempDir, 0777))
                    throw new Exception("Can not create temp dir $tempDir");
                umask($oldumask);
                $this->tempDir = $tempDir;
        } else
            $this->tempDir = $tempDir;
    }

    /**
     * Zip files from list
     * @param string $actualdir
     * @param array $files
     * @param string $archive_name
     * @return string archive name
     */
    function addFiles($actualdir, $files)
    {
        $this->cleanUp();

        $zip = new ZipArchive;
        $tempName = $this->getTempName();
        $zipPath = $this->tempDir . '/' . $tempName;

        if ($zip->open($zipPath, ZipArchive::CREATE) === true) {

                $path = parent::getParent()->getAbsolutePath($actualdir);

                foreach ($files as $file) {
                    $name = $file;
                    $file = $path . $file;

                    if (is_dir($file)) {
                        if (!$zip->addEmptyDir($name))
                            throw new Exception ("Can not add folder '$name' to ZIP archive.");
                    } else {
                        if (!$zip->addFile($file, $name))
                            throw new Exception ("Can not add file '$name' to ZIP archive.");
                    }
                }

                $zip->close();
                return $tempName;
         } else
                throw new Exception ("Can not create ZIP archive '$zipPath' from '$actualdir'.");
    }

    /**
     * Clean old downloads in temp
     */
    function cleanUp()
    {
        $files = Finder::findFiles('*.zip')->in($this->tempDir);

        foreach ($files as $file) {
            $cTime = $file->getCTime();
            $odds = time() - $cTime;
            if ($odds > $this->expiration)
                unlink($file->getPathName());
        }
    }

    /**
     * Get temporary file name
     * @return string
     */
    function getTempName()
    {
        return md5(time() . mt_rand(5, 10)) . '.zip';
    }

    /**
     * Get path to temp dir for downloads
     * @return string
     */
    function getTempDir()
    {
        return $this->tempDir;
    }
}