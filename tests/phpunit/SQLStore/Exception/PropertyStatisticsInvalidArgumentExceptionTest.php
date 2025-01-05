<?php

namespace SMW\Tests\SQLStore\Exception;

use SMW\SQLStore\Exception\PropertyStatisticsInvalidArgumentException;

/**
 * @covers \SMW\SQLStore\Exception\PropertyStatisticsInvalidArgumentException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyStatisticsInvalidArgumentExceptionTest extends \PHPUnit\Framework\TestCase {

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
