<?php

namespace SMW\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
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
class SubSemanticDataExceptionTest extends TestCase {

	public function testCanConstruct() {
		$instance = new SubSemanticDataException();

		$this->assertInstanceof(
			SubSemanticDataException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
