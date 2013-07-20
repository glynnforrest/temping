<?php

namespace Temping\Tests;

use Temping\Temping;

use \SplFileObject;

include(__DIR__ . '/../../bootstrap.php');

/**
 * TempingTest
 *
 * @author Glynn Forrest <me@glynnforrest.com>
 **/
class TempingTest extends \PHPUnit_Framework_TestCase {

	public function tearDown() {
		Temping::getInstance()->reset();
	}

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
		$temp->reset();
		$this->assertFileNotExists($this->createFilePath(null));
	}

	public function testTempingDirRecreatedAfterReset() {
		$temp = Temping::getInstance();
		$temp->reset();
		$this->assertFileNotExists($this->createFilePath(null));
		$another_instance = Temping::getInstance();
		$this->assertFileExists($this->createFilePath(null));
	}

	public function testTempingDirRecreatedAfterResetSameInstance() {
		$temp = Temping::getInstance();
		$temp->reset();
		$this->assertFileNotExists($this->createFilePath(null));
		$filename = '.file';
		$temp->create($filename);
		$this->assertFileExists($this->createFilePath(null));
	}

	public function testCreateSingleFile() {
		$temp = Temping::getInstance();
		$filename = 'file.txt';
		$temp->create($filename);
		$filepath = $this->createFilePath($filename);
		$this->assertFileExists($filepath);
		$temp->reset();
		$this->assertFileNotExists($filepath);
	}

	public function testCreateFileWithinDirectory() {
		$temp = Temping::getInstance();
		$filename = '.hidden/secrets.gpg';
		$temp->create($filename);
		$filepath = $this->createFilePath($filename);
		$this->assertFileExists($filepath);
		$temp->reset();
		$this->assertFileNotExists($filepath);
	}

	public function testCreateFileDeepWithinDirectory() {
		$temp = Temping::getInstance();
		$filename = 'deeply/nested/dirs/with/stuff/in/file.php';
		$temp->create($filename);
		$filepath = $this->createFilePath($filename);
		$this->assertFileExists($filepath);
		$temp->reset();
		$this->assertFileNotExists($filepath);
	}

	public function testCreateFileWithContents() {
		$temp = Temping::getInstance();
		$filename = 'text/files/message.txt';
		$temp->create($filename, 'Hello world');
		$filepath = $this->createFilePath($filename);
		$this->assertFileExists($filepath);
		$this->assertEquals('Hello world', file_get_contents($filepath));
		$temp->reset();
		$this->assertFileNotExists($filepath);
	}

	public function testCreateReturnsId() {
		$temp = Temping::getInstance();
		$filename = 'file';
		$id = $temp->create($filename);
		$this->assertEquals(1, $id);
		$other_id = $temp->create($filename);
		$this->assertEquals(2, $other_id);
	}

	public function testResetResetInternalFilesArray() {
		$temp = Temping::getInstance();
		$filename = 'file';
		$id = $temp->create($filename);
		$this->assertEquals(1, $id);
		$temp->reset();
		$other_id = $temp->create($filename);
		$this->assertEquals(1, $other_id);
	}

	public function testGetFileObject() {
		$temp = Temping::getInstance();
		$filename = 'file';
		$id = $temp->create($filename);
		$file_object = $temp->getFileObject($id);
		$this->assertTrue($file_object instanceof SplFileObject);
		$this->assertEquals(
			$this->createFilePath($filename), $file_object->getPathname());
	}

	public function testGetContents() {
		$temp = Temping::getInstance();
		$filename = 'test/file_with_contents.txt';
		$content = 'Content of text file';
		$id = $temp->create($filename, $content);
		$this->assertEquals($content, $temp->getContents($id));
		$this->assertEquals($content, $temp->getContents($filename));
	}


}