<?php

namespace SMW\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use SMW\Exception\DataItemException;
use SMW\Exception\PropertyLabelNotResolvedException;

/**
 * @covers \SMW\Exception\PropertyLabelNotResolvedException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyLabelNotResolvedExceptionTest extends TestCase {

	public function testCanConstruct() {
		$instance = new PropertyLabelNotResolvedException();

		$this->assertInstanceof(
			PropertyLabelNotResolvedException::class,
			$instance
		);

		$this->assertInstanceof(
			DataItemException::class,
			$instance
		);
	}

}
