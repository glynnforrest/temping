<?php

namespace Temping\Tests;

use Temping\Temping;

include(__DIR__ . '/../../bootstrap.php');

/**
 * TempingTest
 *
 * @author Glynn Forrest <me@glynnforrest.com>
 **/
class TempingTest extends \PHPUnit_Framework_TestCase {

	protected function createFilePath($filename) {
		$tmp_dir = sys_get_temp_dir();
		if(substr($tmp_dir, -1) !== '/') {
			$tmp_dir .= '/';
		}
		return $tmp_dir . Temping::temping_dir_name . $filename;
	}

	public function testGetInstance() {
		$temp = Temping::getInstance();
		$this->assertTrue($temp instanceof Temping);
	}

	public function testTempingDirCreatedOnConstruct() {
		$temp = Temping::getInstance();
		$this->assertFileExists($this->createFilePath(null));
	}

	public function testTempingDirRemoved() {
		$temp = Temping::getInstance();
		$temp->destroy();
		$this->assertFileNotExists($this->createFilePath(null));
	}

	public function testTempingDirRecreatedAfterDestroy() {
		$temp = Temping::getInstance();
		$temp->destroy();
		$this->assertFileNotExists($this->createFilePath(null));
		$another_instance = Temping::getInstance();
		$this->assertFileExists($this->createFilePath(null));
	}

}