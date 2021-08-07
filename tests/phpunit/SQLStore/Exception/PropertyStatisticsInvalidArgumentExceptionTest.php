<?php

namespace SMW\Tests\SQLStore\Exception;

use SMW\SQLStore\Exception\PropertyStatisticsInvalidArgumentException;

/**
 * @covers \SMW\SQLStore\Exception\PropertyStatisticsInvalidArgumentException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyStatisticsInvalidArgumentExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new PropertyStatisticsInvalidArgumentException();

		$this->assertInstanceof(
			'\SMW\SQLStore\Exception\PropertyStatisticsInvalidArgumentException',
			$instance
		);

		$this->assertInstanceof(
			'\InvalidArgumentException',
			$instance
		);
	}

}
