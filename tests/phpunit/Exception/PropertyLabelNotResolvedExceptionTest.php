<?php

namespace SMW\Tests\Exception;

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
class PropertyLabelNotResolvedExceptionTest extends \PHPUnit\Framework\TestCase {

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
