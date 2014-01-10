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
		$tamper_file = substr($this->createFilePath(null), 0, -1);
		if(is_file($tamper_file)) {
			unlink($tamper_file);
		}

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

	public function testTempingDirCreatedThenRemovedAfterReset() {
		$this->assertInstanceOf('\Temping\Temping', $this->temp->init());
		$this->assertFileExists($this->createFilePath(null));
		$this->assertInstanceOf('\Temping\Temping', $this->temp->reset());
		$this->assertFileNotExists($this->createFilePath(null));
	}

	public function testCreateDirectory() {
		$dir = 'storage';
		$this->assertInstanceOf('\Temping\Temping', $this->temp->createDirectory($dir));
		$filepath = $this->createFilePath($dir);
		$this->assertTrue(is_dir($filepath));
		$this->temp->reset();
		$this->assertFalse(is_dir($filepath));
	}

	public function testCreateNestedDirectory() {
		$dir = '/some/deep/nested/directory';
		$this->assertInstanceOf('\Temping\Temping', $this->temp->createDirectory($dir));
		$filepath = $this->createFilePath($dir);
		$this->assertTrue(is_dir($filepath));
		$this->temp->reset();
		$this->assertFalse(is_dir($filepath));
	}

	public function testCreateSingleFile() {
		$filename = 'file.txt';
		$this->assertInstanceOf('\Temping\Temping', $this->temp->create($filename));
		$filepath = $this->createFilePath($filename);
		$this->assertFileExists($filepath);
		$this->temp->reset();
		$this->assertFileNotExists($filepath);
	}

	public function testTempingDirRecreatedAfterReset() {
		$this->temp->reset();
		$this->assertFileNotExists($this->createFilePath(null));
		$another_instance = new Temping();
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
		$this->assertInstanceOf('\Temping\Temping', $this->temp->create($filename));
		$filepath = $this->createFilePath($filename);
		$this->assertFileExists($filepath);
		$this->temp->reset();
		$this->assertFileNotExists($filepath);
	}

	public function testCreateFileDeepWithinDirectory() {
		$filename = 'deeply/nested/dirs/with/stuff/in/file.php';
		$this->assertInstanceOf('\Temping\Temping', $this->temp->create($filename));
		$filepath = $this->createFilePath($filename);
		$this->assertFileExists($filepath);
		$this->temp->reset();
		$this->assertFileNotExists($filepath);
	}

	public function testCreateFileStartingWithSlash () {
		$filename = '/folder/file.txt';
		$this->assertInstanceOf('\Temping\Temping', $this->temp->create($filename));
		$filepath = $this->createFilePath($filename);
		$this->assertFileExists($filepath);
	}

	public function testCreateFileWithManySlashes() {
		$filename = 'file//with////toomanyslashes';
		$this->assertInstanceOf('\Temping\Temping', $this->temp->create($filename));
		$filepath = $this->createFilePath($filename);
		$this->assertFileExists($filepath);
	}

	public function testCreateFileWithContents() {
		$filename = 'text/files/message.txt';
		$this->assertInstanceOf('\Temping\Temping', $this->temp->create($filename, 'Hello world'));
		$filepath = $this->createFilePath($filename);
		$this->assertFileExists($filepath);
		$this->assertEquals('Hello world', file_get_contents($filepath));
		$this->temp->reset();
		$this->assertFileNotExists($filepath);
	}

	public function testCreateSameFileCanOverwriteContent() {
		$filename = 'my-file.txt';
		$this->assertInstanceOf('\Temping\Temping', $this->temp->create($filename, 'Hello world'));
		$this->assertEquals('Hello world', file_get_contents($this->createFilePath($filename)));
		$this->assertInstanceOf('\Temping\Temping', $this->temp->create($filename, 'Hello again'));
		$this->assertEquals('Hello again', file_get_contents($this->createFilePath($filename)));
	}

	public function testGetFileObject() {
		$filename = 'file.php';
		$this->temp->create($filename);
		$file_object = $this->temp->getFileObject($filename);
		$this->assertInstanceOf('\SplFileObject', $file_object);
		$this->assertEquals(
			$this->createFilePath($filename), $file_object->getPathname());
		$this->assertEquals('php', $file_object->getExtension());
	}

	public function testGetFileObjectThrowsExceptionOnUnknownFile() {
		$filename = 'foo.sql';
		$message = "File not found: '$filename'";
		$this->setExpectedException('\Exception', $message);
		$this->temp->getFileObject($filename);
	}

	public function testGetContents() {
		$filename = 'test/file_with_contents.txt';
		$content = 'Content of text file';
		$this->temp->create($filename, $content);
		$this->assertEquals($content, $this->temp->getContents($filename));
	}

	public function testGetContentsThrowsExceptionOnUnknownFile() {
		$filename = 'file-not-created-yet.txt';
		$message = "File not found: '$filename'";
		$this->setExpectedException('\Exception', $message);
		$this->temp->getContents($filename);
	}

	public function testSetContents() {
		$filename = 'my_message.md';
		$this->temp->create($filename, 'Hello world');
		$filepath = $this->createFilePath($filename);
		$this->assertEquals('Hello world', file_get_contents($filepath));
		$this->assertInstanceOf('\Temping\Temping', $this->temp->setContents($filename, 'Hello again'));
		$this->assertEquals('Hello again', file_get_contents($filepath));
	}

	public function testSetContentsThrowsExceptionOnUnknownFile() {
		$filename = 'unknown';
		$message = "File not found: '$filename'";
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
		$this->temp->create($filename, $content);
		$this->assertEquals($content, $this->temp->getContents($filename));
	}

	public function testGetPathname() {
		$filename = 'folder/my-db.sql';
		$this->temp->create($filename);
		$expected = $this->createFilePath($filename);
		$this->assertEquals($expected, $this->temp->getPathname($filename));
	}

	public function testGetPathnameThrowsExceptionOnUnknownFile() {
		$file = 'unknown';
		$message = "File not found: '$file'";
		$this->setExpectedException('\Exception', $message);
		$this->temp->getPathname($file);
	}

	public function testGetDirectory() {
		$expected = $this->createFilePath(null);
		$this->assertEquals($expected, $this->temp->getDirectory());
		$this->assertStringEndsWith('/', $this->temp->getDirectory());
	}

	public function testExistsFile() {
		$file = 'path/to/foo.txt';
		$this->temp->create($file);

		$result = $this->temp->exists($file);
		$this->assertTrue($result);
		//same as file_exists?
		$expected = file_exists($this->createFilePath($file));
		$this->assertSame($expected, $result);

		$this->temp->reset();

		$result = $this->temp->exists($file);
		$this->assertFalse($result);
		//same as file_exists?
		$expected = file_exists($this->createFilePath($file));
		$this->assertSame($expected, $result);
	}

	public function testExistsDir() {
		$dir = '/path/to/some/dir';
		$this->temp->createDirectory($dir);

		$result = $this->temp->exists($dir);
		$this->assertTrue($result);
		//same as file_exists?
		$expected = file_exists($this->createFilePath($dir));
		$this->assertSame($expected, $result);
		$this->assertTrue(is_dir($this->createFilePath($dir)));

		$this->temp->reset();

		$result = $this->temp->exists($dir);
		$this->assertFalse($result);
		//same as file_exists?
		$expected = file_exists($this->createFilePath($dir));
		$this->assertSame($expected, $result);
		$this->assertFalse(is_dir($this->createFilePath($dir)));
	}

	public function testExistsNoInit() {
		$this->assertFalse($this->temp->exists('foo'));
	}

	public function testExistsNoArg() {
		$this->temp->init();
		$this->assertTrue($this->temp->exists());
		$this->temp->reset();
		$this->assertFalse($this->temp->exists());
	}

	public function testExistsNotCreatedByTemping() {
		$this->temp->init();
		$file = 'bar.txt';
		file_put_contents($this->createFilePath($file), 'hello world');
		$this->assertTrue($this->temp->exists($file));
	}

	public function testGetFileObjectNotCreatedByTemping() {
		$this->temp->init();
		$file = 'bar.txt';
		file_put_contents($this->createFilePath($file), 'hello world');
		$this->assertInstanceOf('\SplFileObject', $this->temp->getFileObject($file));
	}

	public function testGetContentsNotCreatedByTemping() {
		$this->temp->init();
		$file = 'foo.txt';
		file_put_contents($this->createFilePath($file), 'hello world');
		$this->assertSame('hello world', $this->temp->getContents($file));
	}

	public function testIsEmptyForDirectory() {
		$this->assertTrue($this->temp->isEmpty());
		$this->assertTrue($this->temp->isEmpty('foo'));
		$this->temp->createDirectory('foo');
		$this->assertFalse($this->temp->isEmpty());
		$this->assertTrue($this->temp->isEmpty('foo'));
	}

	public function testIsEmptyForFile() {
		$this->temp->create('foo.txt', 'Hello');
		$this->assertFalse($this->temp->isEmpty());
		$this->assertTrue($this->temp->isEmpty('foo'));
		$this->temp->createDirectory('foo');
		$this->assertTrue($this->temp->isEmpty('foo'));
		$this->temp->create('foo/foo.txt', 'Hello');
		$this->assertFalse($this->temp->isEmpty('foo'));
	}

	public function testDeleteFile() {
		$file = 'foo/bar/foo.txt';
		$this->temp->create($file);
		$this->assertFileExists($this->createFilePath($file));
		$this->assertFileExists($this->createFilePath('foo/bar'));
		$this->assertInstanceOf('\Temping\Temping', $this->temp->delete($file));
		$this->assertFileNotExists($this->createFilePath($file));
		$this->assertFileExists($this->createFilePath('foo/bar'));
	}

	public function testDeleteNonExistentFile() {
		$this->assertInstanceOf('\Temping\Temping', $this->temp->delete('not_here'));
		$this->assertFileNotExists($this->createFilePath('not_here'));
	}

	public function testDeleteEmptyDir() {
		$this->temp->createDirectory('foo');
		$this->assertInstanceOf('\Temping\Temping', $this->temp->delete('foo'));
		$this->assertFileNotExists($this->createFilePath('foo'));
	}

	public function testNoDeleteForNonEmptyDir() {
		$this->temp->create('foo/bar.txt');
		$this->assertInstanceOf('\Temping\Temping', $this->temp->delete('foo'));
		$this->assertFileExists($this->createFilePath('foo'));
		$this->assertFileExists($this->createFilePath('foo/bar.txt'));

		$this->assertInstanceOf('\Temping\Temping', $this->temp->delete('foo/bar.txt'));
		$this->assertInstanceOf('\Temping\Temping', $this->temp->delete('foo'));
		$this->assertFileNotExists($this->createFilePath('foo'));
		$this->assertFileNotExists($this->createFilePath('foo/bar.txt'));
	}

	public function testDeleteNonEmptyDir() {
		$this->temp->create('foo/bar.txt');
		$this->assertInstanceOf('\Temping\Temping', $this->temp->delete('foo', true));
		$this->assertFileNotExists($this->createFilePath('foo'));
		$this->assertFileNotExists($this->createFilePath('foo/bar.txt'));
	}

	public function testRemoveDirectoryOutside() {
		$this->temp->init();
		$this->assertFileExists($this->createFilePath(null));
		rmdir($this->createFilePath(null));
		$this->assertFileNotExists($this->createFilePath(null));
		$this->temp->create('foo.txt');
		$this->assertFileExists($this->createFilePath(null));
	}

	public function testChangeDirectoryToFileOutside() {
		$this->assertFileNotExists($this->createFilePath(null));
		$tamper_file = substr($this->createFilePath(null), 0, -1);
		touch($tamper_file);
		$this->setExpectedException('\Exception', "'" . $this->createFilePath(null) . "' is not a directory");
		$this->temp->create('foo.txt');
		//can't unlink $tamper_file after catching an Exception, so it
		//is removed in setup().
	}

	public function testCustomDir() {
		$custom_dir = str_replace(Temping::TEMPING_DIR_NAME, 'my-dir/', $this->createFilePath(null));
		$this->assertFileNotExists($custom_dir);
		$temp = new Temping($custom_dir);
		$temp->init();
		$this->assertFileExists($custom_dir);
		$this->assertSame($custom_dir, $temp->getDirectory());
		$temp->reset();
		$this->assertFileNotExists($custom_dir);
	}

	public function testCustomDirThrowsException() {
		$custom_dir = '/no-chance';
		$this->setExpectedException('\Exception');
		$temp = new Temping($custom_dir);
		$temp->create('foo');
	}

	public function testCustomDirAppendsSlash() {
		$custom_dir = str_replace(Temping::TEMPING_DIR_NAME, 'test-dir', $this->createFilePath(null));
		$temp = new Temping($custom_dir);
		$this->assertNotSame($custom_dir, $temp->getDirectory());
		$this->assertTrue(substr($temp->getDirectory(), -1) === '/');
		$temp->reset();
		$this->assertFileNotExists($custom_dir);
	}

	public function testCustomDirUsage() {
		$custom_dir = str_replace(Temping::TEMPING_DIR_NAME, 'test-dir', $this->createFilePath(null));
		$temp = new Temping($custom_dir);
		$temp->create('foo.txt', 'Hello, Earth');
		$path = $custom_dir . '/foo.txt';
		$this->assertFileExists($path);
		$this->assertSame('Hello, Earth', file_get_contents($path));
		$temp->create('/foo/bar/baz/etc.txt', 'Howdy');
		$long_path = $custom_dir . '/foo/bar/baz/etc.txt';
		$this->assertFileExists($long_path);
		$this->assertSame('Howdy', file_get_contents($long_path));
		$temp->reset();
		$this->assertFileNotExists($path);
		$this->assertFileNotExists($long_path);
		$this->assertFileNotExists($custom_dir);
	}

}