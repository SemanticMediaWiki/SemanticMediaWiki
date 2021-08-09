<?php

namespace SMW\Tests\Exception;

use SMW\Exception\PredefinedPropertyLabelMismatchException;

/**
 * @covers \SMW\Exception\PredefinedPropertyLabelMismatchException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PredefinedPropertyLabelMismatchExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new PredefinedPropertyLabelMismatchException();

		$this->assertInstanceof(
			'\SMW\Exception\PredefinedPropertyLabelMismatchException',
			$instance
		);

		$this->assertInstanceof(
			'\SMW\Exception\PropertyLabelNotResolvedException',
			$instance
		);
	}

}
