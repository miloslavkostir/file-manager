<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

namespace Ixtrum\FileManager\Application;

use Nette\Application\ApplicationException,
    Nette\DirectoryNotFoundException,
    Nette\Image,
    Ixtrum\FileManager\Application\FileSystem\Finder;

/**
 * Image thumbnails.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class Thumbs
{

    /** @var string */
    private $thumbDir;

    /** @var array */
    private $config;

    /** @var array */
    public $supported = array("jpg", "jpeg", "png", "gif", "bmp");

    /**
     * Constructor
     *
     * @param array $config application configuration
     */
    public function __construct($config)
    {
        $thumbDir = $config["tempDir"] . "/cache/_Ixtrum.FileManager/thumbs";
        if (!is_dir($thumbDir)) {

            $oldumask = umask(0);
            mkdir($thumbDir, 0777);
            umask($oldumask);
        }

        if (!is_writable($thumbDir)) {
            throw new ApplicationException("Thumb dir '$thumbDir' is not writeable!");
        }

        $this->thumbDir = $thumbDir;
        $this->config = $config;
    }

    /**
     * Get thumb path
     *
     * @param string $path File path
     *
     * @return string
     */
    public function getThumbPath($path)
    {
        return "$this->thumbDir/" . $this->getName($path);
    }

    /**
     * Get thumb file
     *
     * @param string $path File path
     *
     * @return Nette\Image
     */
    public function getThumbFile($path)
    {
        $thumbPath = $this->getThumbPath($path);

        if (file_exists($thumbPath)) {
            return Image::fromFile($thumbPath);
        } else {

            $status = true;
            if (function_exists("exec")) {
                exec("convert -version", $results, $status);
            }

            if (class_exists("\Nette\ImageMagick") && !$status) {
                $image = new \Nette\ImageMagick($path);
            } elseif (class_exists("\Imagick")) {
                $thumb = new \Imagick($path);
                $thumb->resizeImage(96, null, \Imagick::FILTER_LANCZOS, 1);
                $thumb->writeImage($thumbPath);
                $thumb->destroy();

                return Image::fromFile($path);
            } else {
                $image = Image::fromFile($path);
            }

            $image->resize(96, null);
            $image->save($thumbPath, 80);

            return $image;
        }
    }

    /**
     * Encode thumb name
     *
     * @param string $path File path
     *
     * @return string
     */
    private function getName($path)
    {
        $path = realpath($path);
        return md5($path) . "." . pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Delete all thumbs in folder recursively
     *
     * @param string $dirPath Dir path
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
                if (file_exists($thumbPath)) {
                    unlink($thumbPath);
                }
            }
        } else {
            throw new DirectoryNotFoundException("Given path $dirPath does not exist.");
        }
    }

    /**
     * Delete thumb
     *
     * @param string $path Thumb path
     */
    public function deleteThumb($path)
    {
        $thumbPath = $this->getThumbPath($path);
        if (file_exists($thumbPath)) {
            unlink($thumbPath);
        }
    }

}