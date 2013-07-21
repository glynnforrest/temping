<?php

namespace Temping;

use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
use \SplFileObject;

/**
 * Temping
 *
 * @author Glynn Forrest <me@glynnforrest.com>
 **/
class Temping {

	const TEMPING_DIR_NAME = 'php-temping/';

	protected static $instance;

	//set to true after init(), false after reset()
	protected $init;

	//path to the temporary directory where all Temping files are
	//created.
	protected $dir;

	//array of created filenames with their an id as the key.
	protected $files = array();

	protected function __construct() {
	}

	/**
	 * Get an instance of Temping, ensuring a temporary directory has
	 * been created.
	 *
	 * @return Temping instance of the Temping class.
	 */
	public static function getInstance() {
		if(!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Create the temporary directory if it doesn't exist.
	 */
	protected function init() {
		$directory = sys_get_temp_dir();
		if(substr($directory, -1) !== '/') {
			$directory .= '/';
		}
		$this->dir = $directory . self::TEMPING_DIR_NAME;
		if(!file_exists($this->dir)) {
			mkdir($this->dir, 0777);
		}
		$this->init = true;
	}

	/**
	 * Delete the temporary directory created by Temping and all files
	 * within it.
	 */
	public function reset() {
		if(!$this->init) {
			return true;
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
		$this->files = array();
		$this->init = false;
	}

	/**
	 * Create $filename containing $content in the temporary
	 * directory. Folders will be automatically created if they don't
	 * exist.
	 * @param string $filename File path of the file to create.
	 * @param string $content Content to write to the file.
	 * @return int $id The id of the created file.
	 */
	public function create($filename, $content = null) {
		$this->init();
		$last_slash = strrpos($filename, '/');
		if($last_slash) {
			$path = $this->dir . substr($filename, 0, $last_slash);
			if(!file_exists($path)) {
				mkdir($path, 0777, true);
			}
		}
		$filepath = $this->dir . $filename;
		$file = new SplFileObject($filepath, 'w');
		$file->fwrite($content);
		//check if the file has been used before
		$id = array_search($filename, $this->files);
		if($id !== false) {
			return $id + 1;
		}
		$this->files[] = $filename;
		end($this->files);
		//don't allow an id of 0 to be returned due to PHP's weak types
		return key($this->files) + 1;
	}

	/**
	 * Get an instance of SplFileObject for a file.
	 *
	 * @param mixed $id_or_filename The id returned by create() or the
	 * filename passed to the create().
	 * @param string $mode The type of access to the file, same as fopen().
	 * @return \SplFileObject
	 * @throws \Exception
	 */
	public function getFileObject($id_or_filename, $mode = 'r') {
		if(in_array($id_or_filename, $this->files)) {
			//filename supplied
			$filename = $id_or_filename;
		} else {
			//check for id
			//decrement id by 1 as an id of 0 should not be exposed to
			//the user due to PHP's weak types.
			$id_or_filename = (int) $id_or_filename - 1;
			if(array_key_exists($id_or_filename, $this->files)) {
				$filename = $this->files[$id_or_filename];
			} else {
				throw new \Exception("File or id not found: $id_or_filename");
			}
		}
		return new SplFileObject($this->dir . $filename, $mode);
	}

	/**
	 * Get the contents of a file.
	 *
	 * @param mixed $id_or_filename The id returned by create() or the
	 * filename passed to create().
	 * @return string The contents of the file.
	 */
	public function getContents($id_or_filename) {
		$file_object = $this->getFileObject($id_or_filename);
		return file_get_contents($file_object->getPathname());
	}

	/**
	 * Write $content to a file.
	 *
	 * @param mixed $id_or_filename The id returned by create() or the
	 * filename passed to create().
	 * @param string $content The content to write to the file.
	 * @return The number of bytes written
	 * @throws \Exception When the write failed.
	 */
	public function setContents($id_or_filename, $content) {
		$file_object = $this->getFileObject($id_or_filename, 'w');
		$bytes = $file_object->fwrite($content);
		if($bytes) {
			return $bytes;
		}
		throw new \Exception("Unable to write to " . $file_object->getPathname());
	}

	/**
	 * Get the full path name of a file.
	 *
	 * @param mixed $id_or_filename The id returned by create() or the
	 * filename passed to create().
	 * @return string The full path name of the file.
	 */
	public function getPathname($id_or_filename) {
		$file_object = $this->getFileObject($id_or_filename, 'r');
		return $file_object->getPathname();
	}

}
