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
            "glynnforrest/temping": "dev-master"
        }
    }

And run composer to update your dependencies:

    $ curl -s http://getcomposer.org/installer | php
    $ php composer.phar update


## Usage

Creating a blank file

    $temp = Temping\Temping::getInstance();
    $temp->create('my-file.txt');
    //automatically create subdirectories too
    $temp->create('file/in/sub/directory.php')

Creating an empty directory

    $temp->createDirectory('storage');
    //automatically create subdirectories too
    $temp->createDirectory('storage/with/sub/directories')

Creating a file with contents

    $temp->create('my/file.txt', 'Hello, world!');

The create() method returns an id so you can work with the contents of
the file later, using setContents() and getContents().

    $id = $temp->create('file.txt');
    $temp->setContents($id, 'Hello, world!');
    echo $temp->getContents($id);
    //Hello, world!

If you don't have access to the id returned from the create method you
can use the filename specified in the create() method too.

    $filename = 'file.txt';
    $temp->create($filename);
    $temp->setContents($filename, 'Hello, world!');
    echo $temp->getContents($filename);
    //Hello, world!

Get the full path name of a file

    $id = $temp->create('my-file.php');
    echo $temp->getPathname($id);
    // /tmp/php-temping/my-file.php

    //OR

    $filename = 'my-file.php';
    $temp->create($filename);
    echo $temp->getPathname($filename);
    // /tmp/php-temping/my-file.php

Get the full path to the Temping directory

    echo $temp->getDirectory();
    // /tmp/php-temping/

To do other fancy things with your temporary files, you can grab a
SplFileObject instance.

    $id = $temp->create('my-file.php');
    $obj = $temp->getFileObject($id);
    echo $obj->getExtension();
    //php

    //OR

    $filename = 'my-file.php';
    $temp->create($filename);
    $obj = $temp->getFileObject($filename);
    echo $obj->getExtension();
    //php

The default mode of the SplFileObject is read-only, 'r'. Pass any
accepted parameter to fopen() in the second argument to get a
different mode.

    $filename = 'my-file.txt;
    $temp->create($filename);
    $obj = $temp->getFileObject($filename, 'w');
    //Now able to write to my-file.txt

To check if a file or directory has been created, use exists(). You
can also use the $id returned by create() for checking a file.

    $this->temp->createDirectory('some/dir');
    $this->temp->exists('some/dir');
    //true

    $id = $this->temp->create('foo/bar.txt');
    $this->temp->exists('foo/bar.txt);
    //true
    $this->temp->exists($id);
    //true

    $this->temp->exists('something');
    //false


Finally, to obliterate all the temporary files you've created, call
reset().

    $temp->create('file1.txt');
    $temp->create('file2.txt');
    $temp->create('file3.txt');
    $temp->reset();

Now armed with Temping, MyFilesUsingTestCase can be refactored.

    <?php

    class MyFilesUsingTestCase extends \PHPUnit_Framework_TestCase {

        const file = 'file.txt';
        const file2 = 'file2.txt';

        protected $temp;

        public function setUp() {
            $this->temp = Temping\Temping::getInstance();
            $this->temp->create(self::file, 'Hello, world!');
            $this->temp->create(self::file2, 'Hello, again!);
        }

        public function tearDown() {
            $this->temp->reset();
        }

        public function testSomething() {
            $this->temp->create('another-file');
        }

    }

Much better!


### Where are the files stored?

Internally, Temping uses the output of sys\_get\_temp_dir() to decide
where to store the temporary files.

For example:

'file.txt' => '/tmp/php-temping/file.txt'

'dir/subdir/file.txt' => '/tmp/php-temping/dir/subdir/file.txt'
