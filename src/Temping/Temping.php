<?php

namespace Temping;

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
			mkdir($this->dir);
		}
	}

	/**
	 * Delete the temporary directory created by Temping and all files
	 * within it.
	 */
	public function destroy() {
		if(file_exists($this->dir)) {
			rmdir($this->dir);
		}
	}



}
