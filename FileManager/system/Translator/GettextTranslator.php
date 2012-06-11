<?php

namespace Ixtrum\FileManager\System\Translator;

use Nette\Object,
        Nette\Localization\ITranslator,
        Nette\InvalidArgumentException,
        Nette\Utils\Strings,
        Ixtrum\FileManager\System\Caching;

/**
 * Gettext translator.
 * This solution is partitionaly based on Zend_Translate_Adapter_Gettext (c) Zend Technologies USA Inc. (http://www.zend.com), new BSD license
 *
 * @author     Roman Sklenář
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    New BSD License
 * @example    http://addons.nettephp.com/gettext-translator
 * @package    Nette\Extras\GettextTranslator
 * @version    0.4
 */
class GettextTranslator extends Object implements ITranslator
{
	/** @var string */
	public $locale;

	/** @var bool */
	private $endian = FALSE;

	/** @var string filepath */
	protected $filePath = FALSE;

	/** @var array  translation table */
	protected $dictionary = array();

	/** @var array */
	protected $meta;

	/** @var Caching */
	private $cache;

        /** @var bool */
        public $useCache = true;


	/**
	 * Translator contructor.
	 * @param string
         * @param Caching
	 * @param string
	 * @return void
	 */
	public function __construct($filePath, Caching $cache, $locale)
	{
		$this->locale = $locale;
                $this->filePath = $filePath;
                $this->cache = $cache;

		$this->loadDictionary();
	}


	/**
	 * Translates the given string.
	 * @param  string	translation string
	 * @param  int		count (positive number)
	 * @return string
	 */
	public function translate($message, $count = 1)
	{
		$message = (string) $message;
		if (!empty($message) && isset($this->dictionary[$message])) {
			$word = $this->dictionary[$message];
			if (!is_int($count))
                            $count = 1;

			$s = preg_replace('/([a-z]+)/', '$$1', "n=$count;" . $this->meta['Plural-Forms']);
			eval($s);
			$message = $word->translate();
		}

		$args = func_get_args();
		if (count($args) > 1) {
			array_shift($args);
			$message = vsprintf($message, $args);
		}
		return $message;
	}

        /**
         * Load translation data (MO file reader) and dictionary.
         * @throws InvalidArgumentException
         */
        private function loadDictionary()
        {
                $file = $this->filePath;
                if ($file && file_exists($file)) {

                        $cache = $this->cache;
                        $key = "dictionary-$this->locale";

                        if ($cache && $this->useCache) {

                                $cacheItem = $cache->getItem($key);
                                if ($cacheItem) {
                                        $dictionary = $cacheItem;
                                } else {
                                        $dictionary = $this->buildDictionary($file);
                                        $cache->saveItem($key, $dictionary, array(
                                            "files" => array($file),
                                            "tags" => array($key)
                                        ));
                                }

                                $this->dictionary = $dictionary;
                        } else {
                                $dictionary = $this->buildDictionary($file);
                                $this->dictionary = $dictionary;
                                if ($this->useCache) {
                                        $cache->save($key, $dictionary, array(
                                            "files" => array($file),
                                            "tags" => array($key)
                                        ));
                                }
                        }

                }
        }

        /**
	 * Builds the dictionary.
	 * @param  string  $filename  MO file to add, full path must be given for access
	 * @throws InvalidArgumentException
	 * @return void
	 */
	private function buildDictionary($filename)
	{
		$this->endian = FALSE;
		$file = @fopen($filename, 'rb');
		if (!$file) {
			throw new InvalidArgumentException("Error opening translation file '$filename'.");
		}
		if (@filesize($filename) < 10) {
			throw new InvalidArgumentException("'$filename' is not a gettext file.");
		}

		// get endian
		$input = $this->readMoData(1, $file);
		if (strtolower(substr(dechex($input[1]), -8)) == "950412de") {
			$this->endian = FALSE;
		} else if (strtolower(substr(dechex($input[1]), -8)) == "de120495") {
			$this->endian = TRUE;
		} else {
			throw new InvalidArgumentException("'$filename' is not a gettext file.");
		}
		// read revision - not supported for now
		$input = $this->readMoData(1, $file);

		// number of bytes
		$input = $this->readMoData(1, $file);
		$total = $input[1];

		// number of original strings
		$input = $this->readMoData(1, $file);
		$originalOffset = $input[1];

		// number of translation strings
		$input = $this->readMoData(1, $file);
		$translationOffset = $input[1];

		// fill the original table
		fseek($file, $originalOffset);
		$origtemp = $this->readMoData(2 * $total, $file);
		fseek($file, $translationOffset);
		$transtemp = $this->readMoData(2 * $total, $file);

		for ($count = 0; $count < $total; ++$count) {
			if ($origtemp[$count * 2 + 1] != 0) {
				fseek($file, $origtemp[$count * 2 + 2]);
				$original = @fread($file, $origtemp[$count * 2 + 1]);
			} else {
				$original = '';
			}

			if ($transtemp[$count * 2 + 1] != 0) {
				fseek($file, $transtemp[$count * 2 + 2]);
				$tr = fread($file, $transtemp[$count * 2 + 1]);
				if ($original === '') {
					$this->generateMeta($tr);
					continue;
				}

				$word = new Word(explode(Strings::chr(0x00), $original), explode(Strings::chr(0x00), $tr));
				$dictionary[$word->message] = $word;
			}
		}
		return $dictionary;
	}


	/**
	 * Read values from the MO file.
	 * @param  string
         * @param  string|stream  MO gettext file
	 */
	private function readMoData($bytes, $file)
	{
		$data = fread($file, 4 * $bytes);
		return $this->endian === FALSE ? unpack('V' . $bytes, $data) : unpack('N' . $bytes, $data);
	}


	/**
	 * Generates meta information about distionary.
	 * @return void
	 */
	private function generateMeta($s)
	{
		$s = trim($s);

		$s = preg_split('/[\n,]+/', $s);
		foreach ($s as $meta) {
			$pattern = ': ';
			$tmp = preg_split("($pattern)", $meta);
			$this->meta[trim($tmp[0])] = count($tmp) > 2 ? ltrim(strstr($meta, $pattern), $pattern) : $tmp[1];
		}
	}
}


/**
 * Class that represents translatable word.
 *
 * @author     Roman Sklenář
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    New BSD License
 * @example    http://addons.nettephp.com/gettext-translator
 * @package    Nette\Extras\GettextTranslator
 * @version    0.4
 */
class Word extends Object
{
	/** @var string|array */
	protected $message;

	/** @var string|array */
	protected $translation;

	/**
	 * Word constructor.
	 * @param string|array
	 * @param string|array
	 * @return void
	 */
	public function __construct($message, $translation)
	{
		$this->message = $message;
		$this->translation = $translation;
	}


	/**
	 * @return string
	 */
	public function getTranslation($form = 0)
	{
		return is_array($this->translation) ? $this->translation[$form] : $this->translation;
	}


	/**
	 * @return string
	 */
	public function getMessage($form = 0)
	{
		return is_array($this->message) ? $this->message[$form] : $this->message;
	}


	/**
	 * Translates a word.
	 * @param  string  translation string
	 * @param  int     form of translation
	 * @return string
	 */
	public function translate($form = 0)
	{
		$msg = $this->getTranslation($form);
		return !empty($msg) ? $msg : $this->getMessage($form);
	}
}