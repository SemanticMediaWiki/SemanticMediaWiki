<?php

namespace SMW\Tests\Exception;

use SMW\Exception\PropertyNotFoundExeption;

/**
 * @covers \SMW\Exception\PropertyNotFoundExeption
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyNotFoundExeptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new PropertyNotFoundExeption();

		$this->assertInstanceof(
			'\SMW\Exception\PropertyNotFoundExeption',
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
