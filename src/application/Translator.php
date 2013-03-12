<?php

namespace Ixtrum\FileManager\Application;

class Translator implements \Nette\Localization\ITranslator
{

    /** @var array */
    private $dictionary;

    /** @var boolean */
    private $extract = false;

    /** @var string */
    private $langFile;

    /**
     * Constructor
     *
     * @param string $langFile Language file
     */
    public function __construct($langFile)
    {
        $this->langFile = $langFile;
        $this->dictionary = $this->loadDictionary();
    }

    /**
     * Translates the given string.
     *
     * @param string  Message
     * @param integer Plural count - @todo
     *
     * @return string
     */
    public function translate($message, $count = null)
    {
        $message = (string) $message;
        if (!empty($message) && isset($this->dictionary[$message])) {

            $translation = $this->dictionary[$message];
            if (!empty($translation)) {
                $message = $translation;
            }
        } elseif ($this->extract) {
            $this->dictionary[$message] = $message;
        }

        $args = func_get_args();
        if (count($args) > 1) {
            array_shift($args);
            $message = vsprintf($message, $args);
        }


        return $message;
    }

    /**
     * Load dictionary
     *
     * @return array
     */
    public function loadDictionary()
    {
        if (file_exists($this->langFile)) {
            return json_decode(file_get_contents($this->langFile), true);
        }
        return array();
    }

    /**
     * Destruct class method
     */
    public function __destruct()
    {
        if ($this->extract) {
            file_put_contents($this->langFile, json_encode($this->dictionary));
        }
    }

}