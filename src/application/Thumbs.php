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

use Nette\ImageMagick,
    Nette\Image,
    Imagick,
    Ixtrum\FileManager\Application\FileSystem\Finder;

/**
 * Image thumbnails.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class Thumbs
{

    /** @var string */
    private $thumbsDir;

    /** @var array */
    public $supported = array("jpg", "jpeg", "png", "gif");

    /**
     * Constructor
     *
     * @param string $thumbsDir Thumbnails dir
     *
     * @throws \Exception
     */
    public function __construct($thumbsDir)
    {
        if (!is_dir($thumbsDir)) {

            $oldumask = umask(0);
            mkdir($thumbsDir, 0777, true);
            umask($oldumask);
        }
        if (!is_writable($thumbsDir)) {
            throw new \Exception("Thumbs directory '$thumbsDir' is not writable!");
        }
        $this->thumbsDir = $thumbsDir;
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
        return "$this->thumbsDir/" . $this->getName($path);
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
                $image = new ImageMagick($path);
            } elseif (class_exists("\Imagick")) {

                $thumb = new Imagick($path);
                $thumb->resizeImage(96, null, Imagick::FILTER_LANCZOS, 1);
                $thumb->writeImage($thumbPath);
                $thumb->destroy();

                return Image::fromFile($path);
            } else {
                $image = Image::fromFile($path);
            }

            $image->resize(96, null, Image::SHRINK_ONLY);
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
     * Delete all thumbs from directory recursively
     *
     * @param string $dir Directory path
     *
     * @throws \Exception
     */
    public function deleteDirThumbs($dir)
    {
        if (!is_dir($dir)) {
            throw new \Exception("Directory '$dir' not found!");
        }

        $mask = $this->supported;
        foreach ($mask as $key => $val) {
            $mask[$key] = "*.$val";
        }

        $files = Finder::findFiles($mask)->from($dir);
        foreach ($files as $file) {

            $thumbPath = $this->getThumbPath($file->getPathname());
            if (file_exists($thumbPath)) {
                unlink($thumbPath);
            }
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