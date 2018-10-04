<?php

namespace SMW\Tests\Exception;

use SMW\Exception\DataItemDeserializationException;

/**
 * @covers \SMW\Exception\DataItemDeserializationException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class DataItemDeserializationExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new DataItemDeserializationException();

		$this->assertInstanceof(
			'\SMW\Exception\DataItemDeserializationException',
			$instance
		);

		$this->assertInstanceof(
			'\SMW\Exception\DataItemException',
			$instance
		);
	}

}
