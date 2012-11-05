<?php

namespace Ixtrum\FileManager\Application;

class Session
{

    /** @var \Nette\Http\SessionSection */
    private $section;

    /**
     * Constructor
     *
     * @param \Nette\Http\SessionSection $section session section
     */
    public function __construct(\Nette\Http\SessionSection $section)
    {
        $this->section = $section;
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