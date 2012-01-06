<?php

class Filesize
{
        const MATH_BCMATH = "BCMath";

        const MATH_GMP = "GMP";

	/**
	 * Which mathematical library use for mathematical operations
	 * @var string (on of constants FileSize::MATH_*)
	 */
	private static $mathLib;


        function __construct()
        {
		if (function_exists("bcadd"))
			self::$mathLib = self::MATH_BCMATH;
		elseif (function_exists("gmp_add"))
			self::$mathLib = self::MATH_GMP;
		else
			throw new \Nette\Application\ApplicationException("You must have installed at least one of mathematical libraries - BC Math or GMP!");
        }

	/**
	 * Returns file size by using native fseek function
	 * @see http://www.php.net/manual/en/function.filesize.php#79023
	 * @see http://www.php.net/manual/en/function.filesize.php#102135
	 * @return string | bool (false when fail)
	 */
	public function sizeNativeSeek($path)
        {
		// This should work for large files on 64bit platforms and for small files every where
		$fp = fopen($path, "rb");
		flock($fp, LOCK_SH);
		if (!$fp) {
			return false;
		}
		$res = fseek($fp, 0, SEEK_END);
		if ($res === 0) {
			$pos = ftell($fp);
			flock($fp, LOCK_UN);
			fclose($fp);
			// $pos will be positive int if file is <2GB
			// if is >2GB <4GB it will be negative number
			if($pos>=0) {
				return (string)$pos;
			}else{
				return sprintf("%u", $pos);
			}
		} else {
			flock($fp, LOCK_UN);
			fclose($fp);
			return false;
		}
	}

	/**
	 * Returns file size by using native fread function
	 * @see http://stackoverflow.com/questions/5501451/php-x86-how-to-get-filesize-of-2gb-file-without-external-program/5504829#5504829
	 * @return string | bool (false when fail)
	 */
	public function sizeNativeRead($path)
        {
		$fp = fopen($path, "rb");
		flock($fp, LOCK_SH);
		if (!$fp) {
			return false;
		}

		rewind($fp);
		$offset = PHP_INT_MAX - 1;

		$size = (string) $offset;
		if (fseek($fp, $offset) !== 0) {
			flock($fp, LOCK_UN);
			fclose($fp);
			return false;
		}
		$chunksize = 1024 * 1024;
		while (!feof($fp)) {
			$readed = strlen(fread($fp, $chunksize));
			if (self::$mathLib == self::MATH_BCMATH) {
				$size = bcadd($size, $readed);
			} elseif (self::$mathLib == self::MATH_GMP) {
				$size = gmp_add($size, $readed);
			} else {
				throw new \Nette\InvalidStateException("No mathematical library available");
			}
		}
		if (self::$mathLib == self::MATH_GMP) {
			gmp_strval($size);
		}
		flock($fp, LOCK_UN);
		fclose($fp);
		return $size;
	}

	/**
	 * Returns file size using curl module
	 * @see http://www.php.net/manual/en/function.filesize.php#100434
	 * @return string | bool (false when fail or cUrl module not available)
	 */
	public function sizeCurl($path)
        {
		// If program goes here, file must be larger than 2GB
		// curl solution - cross platform and really cool :)
		if (function_exists("curl_init")) {
			$ch = @curl_init("file://" . realpath($path));
                        if (!$ch)
                            return false;
			curl_setopt($ch, CURLOPT_NOBODY, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HEADER, true);
			$data = curl_exec($ch);
			curl_close($ch);
			if ($data !== false && preg_match('/Content-Length: (\d+)/', $data, $matches)) {
				return (string) $matches[1];
			}
		} else {
			return false;
		}
	}

	/**
	 * Returns file size by using external program (exec needed)
	 * @see http://stackoverflow.com/questions/5501451/php-x86-how-to-get-filesize-of-2gb-file-without-external-program/5502328#5502328
	 * @return string | bool (false when fail or exec is disabled)
	 */
	public function sizeExec($path)
        {
		// filesize using exec
		if (function_exists("exec")) {
			$escapedPath = escapeshellarg($path);

			if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') { // Windows
				// Try using the NT substition modifier %~z
				$size = trim(exec("for %F in ($escapedPath) do @echo %~zF"));
			}else{ // other OS
				// If the platform is not Windows, use the stat command (should work for *nix and MacOS)
				$size = trim(exec("stat -c%s $escapedPath"));
			}

			// If the return is not blank, not zero, and is number
			if ($size AND ctype_digit($size)) {
				return (string) $size;
			}
		}
		return false;
	}

	/**
	 * Returns file size by using Windows COM interface
	 * @see http://stackoverflow.com/questions/5501451/php-x86-how-to-get-filesize-of-2gb-file-without-external-program/5502328#5502328
	 * @return string | bool (false when fail or COM not available)
	 */
	public function sizeCom($path)
        {
		if (class_exists("COM")) {
			// Use the Windows COM interface
			$fsobj = new \COM('Scripting.FileSystemObject');
			if (dirname($path) == '.')
				$path = ((substr(getcwd(), -1) == DIRECTORY_SEPARATOR) ? getcwd() . basename($path) : getcwd() . DIRECTORY_SEPARATOR . basename($path));
			$f = $fsobj->GetFile($path);
			return (string) $f->Size;
		}
	}
}