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

	public function testConstruct() {
		$temp = new Temping();
		$this->assertTrue($temp instanceof Temping);
		$temp_again = new Temping('/some/random/dir');
		$this->assertTrue($temp_again instanceof Temping);
	}

}