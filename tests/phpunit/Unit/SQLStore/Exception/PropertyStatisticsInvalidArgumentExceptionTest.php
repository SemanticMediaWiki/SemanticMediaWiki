<?php

namespace SMW\Tests\Unit\SQLStore\Exception;

use PHPUnit\Framework\TestCase;
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
class PropertyStatisticsInvalidArgumentExceptionTest extends TestCase {

	public function testCanConstruct() {
		$instance = new PropertyStatisticsInvalidArgumentException();

		$this->assertInstanceof(
			PropertyStatisticsInvalidArgumentException::class,
			$instance
		);

		$this->assertInstanceof(
			'\InvalidArgumentException',
			$instance
		);
	}

}
