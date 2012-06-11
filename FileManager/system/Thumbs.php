<?php

namespace Ixtrum\FileManager\System;

use Nette\Image,
        Nette\Diagnostics\Debugger,
        Nette\DI\Container,
        Nette\Utils\Finder;

class Thumbs
{
    /** @var string */
    private $thumbDir;

    /** @var Container */
    private $context;

    /** @var array */
    private $config;

    /** @var array */
    public $supported = array("jpg", "jpeg", "png", "gif", "bmp");

    public function __construct(Container $container = NULL, $config = array())
    {
        $tempDir = $container->parameters["tempDir"];
        $thumbDir = "$tempDir/file-manager/thumbs";

        if(!is_dir($thumbDir)) {

                $oldumask = umask(0);
                mkdir($thumbDir, 0777);
                umask($oldumask);
        }

        if (!is_writable($thumbDir))
                throw new \Nette\Application\ApplicationException("Thumb dir '$thumbDir' is not writeable!");

        $this->thumbDir = $thumbDir;
        $this->context = $container;
        $this->config = $config;
    }

    /**
     * Get thumb path
     * 
     * @param string $path
     * @return string 
     */
    public function getThumbPath($path)
    {
        return "$this->thumbDir/" . $this->getName($path);
    }

    /**
     * Get thumb file
     * 
     * @param string $path
     * @return Nette\Image
     */
    public function getThumb($path)
    {
        $thumbPath = $this->getThumbPath($path);

        if (file_exists($thumbPath))
                return Image::fromFile($thumbPath);
        else {

                $tools = new Tools($this->context, $this->config);
                $disksize = $tools->diskSizeInfo();

                if ($disksize["spaceleft"] > 50 ) {

                        $status = true;
                        if (function_exists("exec"))
                                exec("convert -version", $results, $status);

                        if (class_exists("\Nette\ImageMagick") && !$status) {
                                $image = new \Nette\ImageMagick($path);
                        } elseif (class_exists("\Imagick")) {
                                $thumb = new \Imagick($path);
                                $thumb->resizeImage(96, NULL, \Imagick::FILTER_LANCZOS, 1);
                                $thumb->writeImage($thumbPath);
                                $thumb->destroy();
                                return Image::fromFile($path);
                        } else
                                $image = Image::fromFile($path);

                        $image->resize(96, NULL);
                        $image->save($thumbPath, 80);
                        return $image;
                } else
                        Debugger::log("Thumb can not be created, there is no free space on disk.", Debugger::WARNING);
        }
    }

    /**
     * Encode thumb name
     * 
     * @param string $path
     * @return string
     */
    private function getName($path)
    {
        $tools = new Tools($this->context, $this->config);
        $path = $tools->getRealPath($path);
        return md5($path) . "." . pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Delete all thumbs in folder recursively
     * 
     * @param string $dirPath
     */
    public function deleteDirThumbs($dirPath)
    {
        if (is_dir($dirPath)) {

                $mask = $this->supported;
                foreach ($mask as $key => $val) {
                        $mask[$key] = "*.$val";
                }

                $files = Finder::findFiles($mask)->from($dirPath);
                foreach ($files as $file) {

                        $thumbPath = $this->getThumbPath($file->getPathname());
                        if (file_exists($thumbPath))
                                unlink($thumbPath);
                }
        } else
                throw new \Nette\DirectoryNotFoundException("Given path $dirPath does not exist.");
    }

    /**
     * Delete thumb
     * 
     * @param string $path
     */
    public function deleteThumb($path)
    {
        $thumbPath = $this->getThumbPath($path);
        if (file_exists($thumbPath))
                unlink($thumbPath);
    }
}