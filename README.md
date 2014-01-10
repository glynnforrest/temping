# Temping
## Work with temporary files and folders easily across OSes.

[![Build Status](https://travis-ci.org/glynnforrest/temping.png)](https://travis-ci.org/glynnforrest/temping)

## Motivation

Working with temporary files in your tests can be a pain. All good
tests should clean up after themselves, but trying to do this when
working with temporary files can be difficult and error-prone.


    <?php

    class MyFilesUsingTestCase extends \PHPUnit_Framework_TestCase {

        const file = '/tmp/test-dir/file.txt';
        const file2 = '/tmp/test-dir/file2.txt';

        public function setUp() {
            //create a directory to put temporary files in
            mkdir('/tmp/test-dir', 0775, true);
            //create the files used in the test
            file_put_contents(self::file, 'Hello, world!');
            file_put_contents(self::file2, 'Hello, again!);
        }

        public function tearDown() {
            //remove all the created files
            unlink(self::file);
            unlink(self::file2);
            //delete the directory
            rmdir('/tmp/test-dir');
            //PHP Warning:  rmdir(/tmp/test-dir): Directory not empty
            //DOH!
        }

        public function testSomething() {
            touch('tmp/test-dir/another-file');
            //test some things
        }

    }

This approach makes it very difficult to efficiently clean up all
temporary files after a test, and doesn't scale well as the test case
grows. And what if the system you're testing on doesn't have /tmp?

Temping abstracts away all this pain to make working with temporary
files easy.

## Installation

Temping is installed via Composer. To add it to your project, simply add it to your
composer.json file:

    {
        "require": {
            "glynnforrest/temping": "0.4.*"
        }
    }

And run composer to update your dependencies:

    $ curl -s http://getcomposer.org/installer | php
    $ php composer.phar update


## Usage

Get a Temping instance

    $temp = new Temping\Temping();
    //or in a custom directory of your choice
    $temp = new Temping\Temping('path/to/my/dir');

Creating a blank file

    $temp->create('my-file.txt');
    //automatically create subdirectories too
    $temp->create('file/in/sub/directory.php')

Creating an empty directory

    $temp->createDirectory('storage');
    //automatically create subdirectories too
    $temp->createDirectory('storage/with/sub/directories')

Creating a file with contents

    $temp->create('my/file.txt', 'Hello, world!');

Work with the contents of the file after creation using setContents()
and getContents().

    $filename = 'file.txt';
    $temp->create($filename);
    $temp->setContents($filename, 'Hello, world!');
    echo $temp->getContents($filename);
    //Hello, world!

Files that are created, modified or deleted outside of the Temping
class are still accessible.

    file_put_contents($temp->getDirectory() . 'foo.txt', 'Hello, world!');
    echo $temp->getContents('foo.txt');
    //Hello, world!

Get the full path name of a file

    $filename = 'my-file.php';
    $temp->create($filename);
    echo $temp->getPathname($filename);
    // /tmp/php-temping/my-file.php

Get the full path to the Temping directory

    echo $temp->getDirectory();
    // /tmp/php-temping/

To do other fancy things with your temporary files, you can grab a
SplFileObject instance.

    $filename = 'my-file.php';
    $temp->create($filename);
    $obj = $temp->getFileObject($filename);
    echo $obj->getExtension();
    //php

The default mode of the SplFileObject is read-only, 'r'. Pass any
accepted parameter to fopen() as the second argument to get a
different mode.

    $filename = 'my-file.txt';
    $temp->create($filename);
    $obj = $temp->getFileObject($filename, 'w');
    //Now able to write to my-file.txt

Delete a file or directory

    $temp->delete('my-file.txt');
    $temp->delete('my-directory/');

If a directory is not empty, the deletion will fail. Pass true as a
second argument to delete a non-empty directory too.

    $temp->delete('non-empty-directory/', true);

To check if a file or directory has been created, use exists().

    $this->temp->createDirectory('some/dir');
    $this->temp->exists('some/dir');
    //true

    $this->temp->create('foo/bar.txt');
    $this->temp->exists('foo/bar.txt');
    //true

    $this->temp->exists('something');
    //false

Leave the $path argument blank to check if the temping directory
exists.

    $this->temp->exists();
    //true
    $this->temp->reset();
    $this->temp->exists();
    //false

Exists will also check for files that weren't created by Temping
explicitly, but are still present in the temporary directory. This is
useful for testing code that is expected to create files.

    touch($temp->getDirectory() . 'foo.txt');
    $temp->exists('foo.txt');
    //true

    //In a test. Assume MyLogger takes the log directory in the
    //constructor
    $obj = new MyLogger($temp->getDirectory());
    $obj->log('testing');
    $this->assertTrue($temp->exists('log.log'));

To check if a directory is empty, use isEmpty(). This function will
also return true if the directory doesn't exist.

    $this->temp->createDirectory('foo/bar');
    $this->temp->isEmpty('foo/bar');
    //true
    $this->temp->create('foo/bar/baz.txt');
    $this->temp->isEmpty('foo/bar');
    //false

Leave the $dir argument blank to check if the temping directory is
empty.

    $this->temp->isEmpty();
    //true
    $this->temp->create('foo.php');
    $this->temp->isEmpty();
    //false

To obliterate all the files in the temporary directory, plus the
directory itself, call reset().

    $temp->create('file1.txt')->create('file2.txt')->create('file3.txt');
    $temp->reset();

All files inside the temporary directory will be deleted, including
those that weren't created by Temping explicitly.

If you need to recreate the temporary directory after calling reset(),
use init(). By default, init() is called by all Temping methods that
modify the filesystem.

    $temp->reset();
    //temporary directory doesn't exist any more
    $temp->create('foo');
    //temporary directory recreated automatically

    $obj = new MyLogger($temp->getDirectory());
    $temp->reset();
    //temporary directory doesn't exist any more
    $temp->init();
    //temporary directory recreated for MyLogger to use

Also, if you want to use the temporary directory as the location to
test something, but not actually call any Temping methods, call init()
to create the directory manually.

    $obj = new MyLogger($temp->getDirectory());
    //no methods called on $temp yet, so temporary directory doesn't exist
    $temp->init();
    //temporary directory recreated for MyLogger to use

Now armed with Temping, MyFilesUsingTestCase can be refactored.

    <?php

    class MyFilesUsingTestCase extends \PHPUnit_Framework_TestCase {

        protected $temp;

        public function setUp() {
            $this->temp = new Temping\Temping();
            $this->temp->create('file.txt', 'Hello, world!')
                       ->create('file2.txt', 'Hello, again!);
        }

        public function tearDown() {
            $this->temp->reset();
        }

        public function testSomething() {
            $this->temp->create('another-file');
        }

    }

Much better!

### Chainable methods

Methods return the Temping instance where it makes sense. This makes
it easy to do stuff like this:


    $temp->create('foo')
         ->create('bar', 'Hello world')
         ->setContents('foo', 'bar')
         ->delete('bar')
         ->reset();

The methods init(), reset(), create(),
createDirectory(), delete() and setContents() are chainable.

### Where are the files stored?

If you don't specify a directory in the constructor, Temping uses the
output of sys\_get\_temp_dir() to decide where to store the temporary
files.

For example:

'file.txt' => '/tmp/php-temping/file.txt'

'dir/subdir/file.txt' => '/tmp/php-temping/dir/subdir/file.txt'
