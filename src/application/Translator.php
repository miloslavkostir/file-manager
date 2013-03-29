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

use Nette\Localization\ITranslator;

/**
 * Language translator.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class Translator implements ITranslator
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
            file_put_contents($this->langFile, self::indent(json_encode($this->dictionary)));
        }
    }

    /**
     * Indents a flat JSON string to make it more human-readable.
     *
     * @link http://www.daveperrett.com/articles/2008/03/11/format-json-with-php
     *
     * @param string $json The original JSON string to process.
     *
     * @return string Indented version of the original JSON string.
     */
    public static function indent($json)
    {
        $result = '';
        $pos = 0;
        $strLen = strlen($json);
        $indentStr = '    ';
        $newLine = "\n";
        $prevChar = '';
        $outOfQuotes = true;

        for ($i = 0; $i <= $strLen; $i++) {

            // Grab the next character in the string.
            $char = substr($json, $i, 1);

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;

                // If this character is the end of an element,
                // output a new line and indent the next line.
            } else if (($char == '}' || $char == ']') && $outOfQuotes) {
                $result .= $newLine;
                $pos--;
                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            // Add the character to the result string.
            $result .= $char;

            // If the last character was the beginning of an element,
            // output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
                $result .= $newLine;
                if ($char == '{' || $char == '[') {
                    $pos++;
                }

                for ($j = 0; $j < $pos; $j++) {
                    $result .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        return $result;
    }

}