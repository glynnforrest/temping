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
		$this->temp = new Temping();
	}

	public function tearDown() {
		$this->temp->reset();
	}

	protected function createFilePath($filename) {
		$tmp_dir = sys_get_temp_dir();
		if(substr($tmp_dir, -1) !== '/') {
			$tmp_dir .= '/';
		}
		return $tmp_dir . Temping::TEMPING_DIR_NAME . $filename;
	}

	public function testTempingDirIsNotCreatedOnConstruct() {
		$this->assertFileNotExists($this->createFilePath(null));
	}

	public function testCreateDirectory() {
		$dir = 'storage';
		$this->temp->createDirectory($dir);
		$filepath = $this->createFilePath($dir);
		$this->assertTrue(is_dir($filepath));
		$this->temp->reset();
		$this->assertFalse(is_dir($filepath));
	}

	public function testCreateNestedDirectory() {
		$dir = '/some/deep/nested/directory';
		$this->temp->createDirectory($dir);
		$filepath = $this->createFilePath($dir);
		$this->assertTrue(is_dir($filepath));
		$this->temp->reset();
		$this->assertFalse(is_dir($filepath));
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
		$this->temp->create('file');
		$this->assertFileExists($this->createFilePath(null));
		$this->assertFileExists($this->createFilePath('file'));
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
		$filename = 'file.php';
		$id = $this->temp->create($filename);

		$file_object_id = $this->temp->getFileObject($id);
		$this->assertTrue($file_object_id instanceof SplFileObject);
		$this->assertEquals(
			$this->createFilePath($filename), $file_object_id->getPathname());

		$file_object_filename = $this->temp->getFileObject($filename);
		$this->assertTrue($file_object_filename instanceof SplFileObject);
		$this->assertEquals(
			$this->createFilePath($filename), $file_object_filename->getPathname());
		$this->assertEquals('php', $file_object_id->getExtension());
	}

	public function testGetFileObjectThrowsExceptionOnUnknownFile() {
		$id = 42;
		$message = 'File or id not found: ' . $id;
		$this->setExpectedException('\Exception', $message);
		$this->temp->getFileObject($id);
	}

	public function testGetContents() {
		$filename = 'test/file_with_contents.txt';
		$content = 'Content of text file';
		$id = $this->temp->create($filename, $content);
		$this->assertEquals($content, $this->temp->getContents($id));
		$this->assertEquals($content, $this->temp->getContents($filename));
	}

	public function testGetContentsThrowsExceptionOnUnknownFile() {
		$filename = 'file-not-created-yet.txt';
		$message = 'File or id not found: ' . $filename;
		$this->setExpectedException('\Exception', $message);
		$this->temp->getContents($filename);
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

	public function testSetContentsThrowsExceptionOnUnknownFile() {
		$filename = 'unknown';
		$message = 'File or id not found: ' . $filename;
		$this->setExpectedException('\Exception', $message);
		$this->temp->setContents($filename, 'Some content');
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

	public function testGetPathnameThrowsExceptionOnUnknownFile() {
		$id = 44;
		$message = 'File or id not found: 44';
		$this->setExpectedException('\Exception', $message);
		$this->temp->getPathname($id);
	}

	public function testGetDirectory() {
		$expected = $this->createFilePath(null);
		$this->assertEquals($expected, $this->temp->getDirectory());
		$this->assertStringEndsWith('/', $this->temp->getDirectory());
	}

	public function testExistsFile() {
		$file = 'path/to/foo.txt';
		//id
		$id = $this->temp->create($file);
		$this->assertTrue($this->temp->exists($id));
		//filename
		$result = $this->temp->exists($file);
		$this->assertTrue($result);
		//same as file_exists?
		$expected = file_exists($this->createFilePath($file));
		$this->assertSame($expected, $result);

		$this->temp->reset();

		//id
		$this->assertFalse($this->temp->exists($id));
		//filename
		$result = $this->temp->exists($file);
		$this->assertFalse($result);
		//same as file_exists?
		$expected = file_exists($this->createFilePath($file));
		$this->assertSame($expected, $result);
	}

	public function testExistsDir() {
		$dir = '/path/to/some/dir';
		$this->temp->createDirectory($dir);
		//filename
		$result = $this->temp->exists($dir);
		$this->assertTrue($result);
		//same as file_exists?
		$expected = file_exists($this->createFilePath($dir));
		$this->assertSame($expected, $result);
		$this->assertTrue(is_dir($this->createFilePath($dir)));

		$this->temp->reset();

		//filename
		$result = $this->temp->exists($dir);
		$this->assertFalse($result);
		//same as file_exists?
		$expected = file_exists($this->createFilePath($dir));
		$this->assertSame($expected, $result);
		$this->assertFalse(is_dir($this->createFilePath($dir)));
	}

}