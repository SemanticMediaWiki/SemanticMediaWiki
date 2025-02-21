<?php

namespace SMW\Tests\Exception;

use SMW\Exception\SubSemanticDataException;

/**
 * @covers \SMW\Exception\SubSemanticDataException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SubSemanticDataExceptionTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$instance = new SubSemanticDataException();

		$this->assertInstanceof(
			'\SMW\Exception\SubSemanticDataException',
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
