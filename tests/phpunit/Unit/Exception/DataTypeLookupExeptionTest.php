<?php

namespace SMW\Tests\Exception;

use SMW\Exception\DataTypeLookupExeption;

/**
 * @covers \SMW\Exception\DataTypeLookupExeption
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class DataTypeLookupExeptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new DataTypeLookupExeption();

		$this->assertInstanceof(
			'\SMW\Exception\DataTypeLookupExeption',
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
