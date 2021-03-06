<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

namespace Ixtrum\FileManager\Plugins;

/**
 * ContextMenu interface.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
interface IContextMenu
{

    /**
     * Get context menu object
     */
    public function getContextMenu();
}