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
		return $tmp_dir . Temping::TEMPING_DIR_NAME . $filename;
	}

	public function testGetInstance() {
		$this->assertTrue($this->temp instanceof Temping);
	}

	public function testTempingDirIsNotCreatedOnConstruct() {
		$this->assertFileNotExists($this->createFilePath(null));
	}

	public function testCreateSingleFile() {
		$filename = 'file.txt';
		$this->temp->create($filename);
		$filepath = $this->createFilePath($filename);
		$this->assertFileExists($filepath);
		$this->temp->reset();
		$this->assertFileNotExists($filepath);
	}

	public function testTempingDirCreatedThenRemovedAfterReset() {
		$this->temp->create('file');
		$this->assertFileExists($this->createFilePath(null));
		$this->temp->reset();
		$this->assertFileNotExists($this->createFilePath(null));
	}

	public function testTempingDirRecreatedAfterReset() {
		$this->temp->reset();
		$this->assertFileNotExists($this->createFilePath(null));
		$another_instance = Temping::getInstance();
		$another_instance->create('file');
		$this->assertFileExists($this->createFilePath(null));
	}

	public function testTempingDirRecreatedAfterResetSameInstance() {
		$this->temp->reset();
		$this->assertFileNotExists($this->createFilePath(null));
		$filename = '.file';
		$this->temp->create($filename);
		$this->assertFileExists($this->createFilePath(null));
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

	public function testCreateFileStartingWithSlash () {
		$filename = '/folder/file.txt';
		$this->temp->create($filename);
		$filepath = $this->createFilePath($filename);
		$this->assertFileExists($filepath);
	}

	public function testCreateFileWithManySlashes() {
		$filename = 'file//with////toomanyslashes';
		$this->temp->create($filename);
		$filepath = $this->createFilePath($filename);
		$this->assertFileExists($filepath);
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
		$other_filename = 'file2';
		$other_id = $this->temp->create($other_filename);
		$this->assertEquals(2, $other_id);
	}

	public function testCreateSameFileReturnsSameId() {
		$filename = 'testing/.test';
		$id = $this->temp->create($filename);
		$other_id = $this->temp->create($filename);
		$this->assertEquals($id, $other_id);
	}

	public function testCreateSameFileCanOverwriteContent() {
		$filename = 'my-file.txt';
		$id = $this->temp->create($filename, 'Hello world');
		$this->assertEquals('Hello world', file_get_contents($this->createFilePath($filename)));
		$other_id = $this->temp->create($filename, 'Hello again');
		$this->assertEquals('Hello again', file_get_contents($this->createFilePath($filename)));
		$this->assertEquals($id, $other_id);
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

		$file_object_id = $this->temp->getFileObject($id);
		$this->assertTrue($file_object_id instanceof SplFileObject);
		$this->assertEquals(
			$this->createFilePath($filename), $file_object_id->getPathname());

		$file_object_filename = $this->temp->getFileObject($filename);
		$this->assertTrue($file_object_filename instanceof SplFileObject);
		$this->assertEquals(
			$this->createFilePath($filename), $file_object_filename->getPathname());
	}

	public function testGetContents() {
		$filename = 'test/file_with_contents.txt';
		$content = 'Content of text file';
		$id = $this->temp->create($filename, $content);
		$this->assertEquals($content, $this->temp->getContents($id));
		$this->assertEquals($content, $this->temp->getContents($filename));
	}

	public function testSetContents() {
		$filename = 'my_message.md';
		$id = $this->temp->create($filename, 'Hello world');
		$filepath = $this->createFilePath($filename);
		$this->assertEquals('Hello world', file_get_contents($filepath));
		$this->temp->setContents($filename, 'Hello again');
		$this->assertEquals('Hello again', file_get_contents($filepath));
		$this->temp->setContents($id, 'Hello once more');
		$this->assertEquals('Hello once more', file_get_contents($filepath));
	}

	public function testSetContentsThrowsExceptionOnFailedWrite() {
		$filename = 'my_message.md';
		$this->temp->create($filename, 'Hello world');
		$filepath = $this->createFilePath($filename);
		chmod($filepath, 000);
		$this->setExpectedException('\Exception');
		$this->temp->setContents($filename, 'Hello again');
	}

	public function testFilenamesCanBeIntegers() {
		$filename = 1;
		$content = 'Contents of integer-named file';
		$id = $this->temp->create($filename, $content);
		$this->assertEquals($content, $this->temp->getContents($filename));
		$this->assertEquals($content, $this->temp->getContents($id));
	}

	public function testGetPathname() {
		$filename = 'folder/my-db.sql';
		$id = $this->temp->create($filename);
		$expected = $this->createFilePath($filename);
		$this->assertEquals($expected, $this->temp->getPathname($id));
		$this->assertEquals($expected, $this->temp->getPathname($filename));
	}

}