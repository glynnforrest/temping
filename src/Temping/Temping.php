<?php

namespace Temping;

use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;

/**
 * Temping
 *
 * @author Glynn Forrest <me@glynnforrest.com>
 **/
class Temping {

	const temping_dir_name = 'php-temping/';

	protected static $instance;

	//path to the temporary directory where all Temping files are
	//created.
	protected $dir;

	protected function __construct() {
	}

	/**
	 * Get an instance of Temping, ensuring a temporary directory has
	 * been created.
	 */
	public static function getInstance() {
		if(!self::$instance) {
			self::$instance = new self();
		}
		self::$instance->init();
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
		$this->dir = $directory . self::temping_dir_name;
		if(!file_exists($this->dir)) {
			mkdir($this->dir, 0777);
		}
	}

	/**
	 * Delete the temporary directory created by Temping and all files
	 * within it.
	 */
	public function destroy() {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator(
				$this->dir, RecursiveDirectoryIterator::SKIP_DOTS
			),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ($iterator as $file_info) {
			echo $file_info->getPathname() . PHP_EOL;
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
	}

	/**
	 * Create $filename in the temporary directory. Folders will be
	 * automatically created if they don't exist.
	 */
	public function create($filename) {
		$last_slash = strrpos($filename, '/');
		if($last_slash) {
			$path = $this->dir . substr($filename, 0, $last_slash);
			if(!file_exists($path)) {
				mkdir($path, 0777, true);
			}
		}
		$filepath = $this->dir . $filename;
		touch($filepath);
	}

}
