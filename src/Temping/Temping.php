<?php

namespace Temping;

use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
use \SplFileObject;
use \FilesystemIterator;

/**
 * Temping
 *
 * @author Glynn Forrest <me@glynnforrest.com>
 **/
class Temping {

	const TEMPING_DIR_NAME = 'php-temping/';

	//path to the temporary directory where all Temping files are
	//created.
	protected $dir;

	/**
	 * Get an instance of Temping.
	 *
	 * @param string $directory The location of the temporary
	 * directory. This machine's temporary directory will be used if
	 * left blank.
	 * @return Temping instance of the Temping class.
	 */
	public function __construct($directory = null) {
		if(!$directory) {
			$directory = realpath(sys_get_temp_dir());
			if(substr($directory, -1) !== '/') {
				$directory .= '/';
			}
			$this->dir = $directory . self::TEMPING_DIR_NAME;
		} else {
			if(substr($directory, -1) !== '/') {
				$directory .= '/';
			}
			$this->dir = $directory;
		}
	}

	/**
	 * Ensure the temporary directory exists and is writable.
	 *
	 * @return Temping This Temping instance.
	 */
	public function init() {
		//strip the / off the end to check for existence of a file
		//that has the same name as $this->dir
		if(!file_exists(substr($this->dir, 0, -1)) && is_writable(dirname($this->dir))) {
			mkdir($this->dir, 0777);
		} elseif(!is_dir($this->dir)) {
			throw new \Exception("'$this->dir' is not a directory");
		} elseif(!is_writable($this->dir)) {
			throw new \Exception("'$this->dir' is not writable");
		}
		return $this;
	}

	/**
	 * Delete the temporary directory created by Temping and all files
	 * within it.
	 *
	 * @return Temping This Temping instance.
	 */
	public function reset() {
		$this->delete(null, true);
		return $this;
	}

	/**
	 * Create $file containing $content in the temporary
	 * directory. Directories will be created automatically if they
	 * don't exist.
	 *
	 * @param string $file The path of the file to create, relative to
	 * the temporary directory (e.g. 'foo/bar.txt')
	 * @param string $content Content to write to the file.
	 * @return Temping This Temping instance.
	 */
	public function create($file, $content = null) {
		$this->init();
		$last_slash = strrpos($file, '/');
		if($last_slash) {
			$directory = substr($file, 0, $last_slash);
			$this->createDirectory($directory);
		}
		$filepath = $this->dir . $file;
		$obj = new SplFileObject($filepath, 'w');
		$obj->fwrite($content);
		return $this;
	}

	/**
	 * Create a new directory in the temporary
	 * directory. Sub-directories will be created automatically if
	 * they don't exist.
	 *
	 * @param string $directory The path of the directory to create,
	 * relative to the temporary directory (e.g. 'foo/bar.txt')
	 * @return Temping This Temping instance.
	 */
	public function createDirectory($directory) {
		$this->init();
		$path = $this->dir . $directory;
		if(!file_exists($path)) {
			mkdir($path, 0777, true);
		}
		return $this;
	}

	/**
	 * Delete a file or directory. A directory will only be deleted if
	 * it is empty, or if $recursive is set to true.
	 *
	 * @param string $path The path of the file or directory, relative
	 * to the temporary directory (e.g. 'foo/bar.txt').
	 * @param bool $delete_contents Whether to delete a non-empty
	 * directory
	 * @return Temping This Temping instance.
	 */
	public function delete($path, $recursive = false) {
		$file = $this->dir . $path;
		if(!file_exists($file)) {
			return $this;
		}
		if(!is_dir($file)) {
			unlink($file);
			return $this;
		}
		if(!$this->isEmpty($path) && !$recursive) {
			return $this;
		}
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$this->dir, RecursiveDirectoryIterator::SKIP_DOTS
			),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($iterator as $file_info) {
			if(is_file($file_info->getPathname())) {
				unlink($file_info->getPathname());
			} else {
				//because of CHILD_FIRST, all files will be deleted by now
				rmdir($file_info->getPathname());
			}
		}
		if(file_exists($this->dir)) {
			rmdir($this->dir);
		}
		return $this;
	}

	/**
	 * Get an instance of SplFileObject for a file.
	 *
	 * @param string $file The path of the file, relative to the
	 * temporary directory (e.g. 'foo/bar.txt')
	 * @param string $mode The type of access to the file, same as fopen()
	 * @return \SplFileObject
	 * @throws \Exception
	 */
	public function getFileObject($file, $mode = 'r') {
		$filepath = $this->dir . $file;
		//check the filepath exists. It may have been created,
		//modified or deleted outside of Temping.
		if(!file_exists($filepath) || !is_writable($filepath)) {
			throw new \Exception("File not found: '$file'");
		}
		return new SplFileObject($filepath, $mode);
	}

	/**
	 * Get the contents of a file.
	 *
	 * @param string $file The path of the file, relative to the
	 * temporary directory (e.g. 'foo/bar.txt')
	 * @return string The contents of the file.
	 */
	public function getContents($file) {
		$file_object = $this->getFileObject($file);
		return file_get_contents($file_object->getPathname());
	}

	/**
	 * Write $content to a file.
	 *
	 * @param string $file The path of the file, relative to the
	 * temporary directory (e.g. 'foo/bar.txt')
	 * @param string $content The content to write to the file.
	 * @return Temping This Temping instance.
	 * @throws \Exception When the write failed.
	 */
	public function setContents($file, $content) {
		$file_object = $this->getFileObject($file, 'w');
		if($file_object->fwrite($content)) {
			return $this;
		}
		throw new \Exception("Unable to write to " . $file_object->getPathname());
	}

	/**
	 * Get the full path name of a file. This method will not check if
	 * the file exists.
	 *
	 * @param string $file The path of the file, relative to the
	 * temporary directory (e.g. 'foo/bar.txt')
	 * @return string The full path name of the file.
	 */
	public function getPathname($file) {
        return $this->dir . $file;
	}

	/**
	 * Get the full path name of the Temping directory, ending with a
	 * trailing slash.
	 *
	 * @return string The full path name of the Temping directory.
	 */
	public function getDirectory() {
		return $this->dir;
	}

	/**
	 * Check if a file or directory has been created.
	 *
	 * @param string $path The path of the file or directory, relative
	 * to the temporary directory (e.g. 'foo/bar.txt'). If null, check
	 * if the temping directory exists.
	 * @return bool True if the file exists, false otherwise.
	 */
	public function exists($path = null) {
		return file_exists($this->dir . $path);
	}

	/**
	 * Check if a temporary directory is empty.
	 *
	 * This method will also return true if the directory doesn't exist.
	 *
	 * @param string $directory The path of the directory, relative to the
	 * temporary directory. If null, check the entire temporary
	 * directory.
	 * @return bool True if the directory is empty, false otherwise.
	 */
	public function isEmpty($directory = null) {
		if(!$this->exists($directory)) {
			return true;
		}
		$i = new FilesystemIterator($this->dir . $directory);
		return !$i->valid();
	}

}
