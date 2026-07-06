<?php

namespace SMW\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use SMW\Exception\PredefinedPropertyLabelMismatchException;
use SMW\Exception\PropertyLabelNotResolvedException;

/**
 * @covers \SMW\Exception\PredefinedPropertyLabelMismatchException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class PredefinedPropertyLabelMismatchExceptionTest extends TestCase {

	public function testCanConstruct() {
		$instance = new PredefinedPropertyLabelMismatchException();

		$this->assertInstanceof(
			PredefinedPropertyLabelMismatchException::class,
			$instance
		);

		$this->assertInstanceof(
			PropertyLabelNotResolvedException::class,
			$instance
		);
	}

}
