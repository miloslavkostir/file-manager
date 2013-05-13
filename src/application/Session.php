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

/**
 * Session wrapper.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class Session
{

    /** @var \Nette\Http\SessionSection */
    private $section;

    /**
     * Constructor
     *
     * @param \Nette\Http\Session $session Session instance
     */
    public function __construct(\Nette\Http\Session $session)
    {
        $this->section = $session->getSection("file-manager");
    }

    /**
     * Set variable in session
     *
     * $param string $name  Variable name
     * @param mixed  $value Value
     */
    public function set($name, $value)
    {
        $this->section->$name = $value;
    }

    /**
     * Add value on key in session variable
     *
     * $param string $name  Variable name
     * $param mixed  $name  Array key
     * @param mixed  $value Value
     */
    public function add($name, $key, $value)
    {
        $values = $this->section->$name;
        $values[$key] = $value;
        $this->section->$name = $values;
    }

    /**
     * Remove item from session
     *
     * $param string $name Variable name
     * @param string $key  Array key
     */
    public function remove($name, $key)
    {
        $values = $this->section->$name;
        unset($values[$key]);
        $this->section->$name = $values;
    }

    /**
     * Remove item from session
     *
     * $param string $name Variable name
     */
    public function clear($name)
    {
        unset($this->section->$name);
    }

    /**
     * Get variable from session
     *
     * $param $name Variable name
     *
     * @return mixed
     */
    public function get($name)
    {
        return $this->section->$name;
    }

}