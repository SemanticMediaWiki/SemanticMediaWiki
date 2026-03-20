<?php

namespace SMW\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use SMW\Exception\SemanticDataImportException;

/**
 * @covers \SMW\Exception\SemanticDataImportException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class SemanticDataImportExceptionTest extends TestCase {

	public function testCanConstruct() {
		$instance = new SemanticDataImportException();

		$this->assertInstanceof(
			SemanticDataImportException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
