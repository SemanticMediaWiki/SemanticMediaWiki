<?php

namespace SMW\Tests\Exception;

use SMW\Exception\PropertyLabelNotResolvedException;

/**
 * @covers \SMW\Exception\PropertyLabelNotResolvedException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyLabelNotResolvedExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new PropertyLabelNotResolvedException();

		$this->assertInstanceof(
			'\SMW\Exception\PropertyLabelNotResolvedException',
			$instance
		);

		$this->assertInstanceof(
			'\SMW\Exception\DataItemException',
			$instance
		);
	}

}
