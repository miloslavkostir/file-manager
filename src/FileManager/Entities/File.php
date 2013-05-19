<?php

/**
 * This file is part of the Ixtrum File Manager package (http://ixtrum.com/file-manager)
 *
 * (c) Bronislav Sedlák <sedlak@ixtrum.com>)
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */

namespace Ixtrum\FileManager\Entities;

/**
 * File entity.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class File
{

    /** @var string * */
    public $name;

    /** @var string * */
    public $modified;

    /** @var string * */
    public $size;

    /** @var string * */
    public $extension;

    /** @var string * */
    public $thumb;

}