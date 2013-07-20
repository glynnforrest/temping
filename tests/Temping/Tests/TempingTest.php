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

	protected $temp;

	public function setUp() {
		$this->temp = Temping::getInstance();
	}

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
		$this->assertTrue($this->temp instanceof Temping);
	}

	public function testTempingDirCreatedOnConstruct() {
		$this->assertFileExists($this->createFilePath(null));
	}

	public function testTempingDirRemoved() {
		$this->temp->reset();
		$this->assertFileNotExists($this->createFilePath(null));
	}

	public function testTempingDirRecreatedAfterReset() {
		$this->temp->reset();
		$this->assertFileNotExists($this->createFilePath(null));
		$another_instance = Temping::getInstance();
		$this->assertFileExists($this->createFilePath(null));
	}

	public function testTempingDirRecreatedAfterResetSameInstance() {
		$this->temp->reset();
		$this->assertFileNotExists($this->createFilePath(null));
		$filename = '.file';
		$this->temp->create($filename);
		$this->assertFileExists($this->createFilePath(null));
	}

	public function testCreateSingleFile() {
		$filename = 'file.txt';
		$this->temp->create($filename);
		$filepath = $this->createFilePath($filename);
		$this->assertFileExists($filepath);
		$this->temp->reset();
		$this->assertFileNotExists($filepath);
	}

	public function testCreateFileIllegalPathName() {
	}


	public function testCreateFileWithinDirectory() {
		$filename = '.hidden/secrets.gpg';
		$this->temp->create($filename);
		$filepath = $this->createFilePath($filename);
		$this->assertFileExists($filepath);
		$this->temp->reset();
		$this->assertFileNotExists($filepath);
	}

	public function testCreateFileDeepWithinDirectory() {
		$filename = 'deeply/nested/dirs/with/stuff/in/file.php';
		$this->temp->create($filename);
		$filepath = $this->createFilePath($filename);
		$this->assertFileExists($filepath);
		$this->temp->reset();
		$this->assertFileNotExists($filepath);
	}

	public function testCreateFileWithContents() {
		$filename = 'text/files/message.txt';
		$this->temp->create($filename, 'Hello world');
		$filepath = $this->createFilePath($filename);
		$this->assertFileExists($filepath);
		$this->assertEquals('Hello world', file_get_contents($filepath));
		$this->temp->reset();
		$this->assertFileNotExists($filepath);
	}

	public function testCreateReturnsId() {
		$filename = 'file';
		$id = $this->temp->create($filename);
		$this->assertEquals(1, $id);
		$other_id = $this->temp->create($filename);
		$this->assertEquals(2, $other_id);
	}

	public function testResetResetInternalFilesArray() {
		$filename = 'file';
		$id = $this->temp->create($filename);
		$this->assertEquals(1, $id);
		$this->temp->reset();
		$other_id = $this->temp->create($filename);
		$this->assertEquals(1, $other_id);
	}

	public function testGetFileObject() {
		$filename = 'file';
		$id = $this->temp->create($filename);
		$file_object = $this->temp->getFileObject($id);
		$this->assertTrue($file_object instanceof SplFileObject);
		$this->assertEquals(
			$this->createFilePath($filename), $file_object->getPathname());
	}

	public function testGetContents() {
		$filename = 'test/file_with_contents.txt';
		$content = 'Content of text file';
		$id = $this->temp->create($filename, $content);
		$this->assertEquals($content, $this->temp->getContents($id));
		$this->assertEquals($content, $this->temp->getContents($filename));
	}


}