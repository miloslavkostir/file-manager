<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

namespace Ixtrum\FileManager\Plugins\Objects;

/**
 * Context menu object contains context menu items.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class ContextMenu
{

    /** @var array */
    private $content = array();

    /** @var array */
    private $files = array();

    /**
     * Add new item into the content context menu
     *
     * @param string $link  Link URL
     * @param string $title Link title
     */
    public function addContentMenu($link, $title)
    {
        $this->content[$link] = $title;
    }

    /**
     * Add new item into the files context menu
     *
     * @param string $link  Link URL
     * @param string $title Link title
     */
    public function addFilesMenu($link, $title)
    {
        $this->files[$link] = $title;
    }

    /**
     * Get all items for content context menu
     *
     * @return array
     */
    public function getContentMenu()
    {
        return $this->content;
    }

    /**
     * Get all items for files context menu
     *
     * @return array
     */
    public function getFilesMenu()
    {
        return $this->files;
    }

}