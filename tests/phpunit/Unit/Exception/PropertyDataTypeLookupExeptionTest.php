<?php

namespace SMW\Tests\Exception;

use SMW\Exception\PropertyDataTypeLookupExeption;

/**
 * @covers \SMW\Exception\PropertyDataTypeLookupExeption
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyDataTypeLookupExeptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new PropertyDataTypeLookupExeption();

		$this->assertInstanceof(
			'\SMW\Exception\PropertyDataTypeLookupExeption',
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
