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

use Nette\Localization\ITranslator,
    Nette\Utils\Json,
    Nette\Utils\JsonException;

/**
 * Language translator.
 *
 * @author Bronislav Sedlák <sedlak@ixtrum.com>
 */
class Translator implements ITranslator
{

    /** @var array */
    private $dictionary = array();

    /** @var boolean */
    private $extract = false;

    /** @var string */
    private $langFile;

    /** @var string */
    private $title = "english";

    /** @var string */
    private $timeFormat = "Y-m-d H:i:s";

    /**
     * Constructor
     *
     * @param string $langFile Language file
     */
    public function __construct($langFile)
    {
        $this->langFile = $langFile;
        $this->loadLanguage();
    }

    /**
     * Get language time format
     *
     * @return string
     */
    public function getTimeFormat()
    {
        return $this->timeFormat;
    }

    /**
     * Get language title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Translates the given string.
     *
     * @param string  Word
     * @param integer Plural count - @todo
     *
     * @return string
     */
    public function translate($word, $count = null)
    {
        $word = (string) $word;
        if (!empty($word) && isset($this->dictionary[$word])) {

            $translation = $this->dictionary[$word];
            if (!empty($translation)) {
                $word = $translation;
            }
        } elseif ($this->extract) {
            $this->dictionary[$word] = $word;
        }

        $args = func_get_args();
        if (count($args) > 1) {
            array_shift($args);
            $word = vsprintf($word, $args);
        }
        return $word;
    }

    /**
     * Load language
     *
     * @return void
     *
     * @throws \Exception
     */
    private function loadLanguage()
    {
        if (is_file($this->langFile)) {

            try {
                $language = Json::decode(file_get_contents($this->langFile), true);
            } catch (JsonException $exception) {
                throw new \Exception("Problem with language file '$this->langFile'. " . $exception->getMessage());
            }

            if (isset($language["dictionary"])) {
                $this->dictionary = $language["dictionary"];
            }
            if (isset($language["timeFormat"])) {
                $this->timeFormat = $language["timeFormat"];
            }
            if (isset($language["title"])) {
                $this->title = $language["title"];
            }
        }
    }

    /**
     * Destruct class method
     */
    public function __destruct()
    {
        // If extraction enabled, export language
        if ($this->extract) {

            $language = array();
            $language["timeFormat"] = $this->timeFormat;
            $language["title"] = $this->title;
            $language["dictionary"] = $this->dictionary;
            file_put_contents($this->langFile, self::indent(Json::encode($language)));
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