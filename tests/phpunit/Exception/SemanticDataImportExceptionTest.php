<?php

namespace SMW\Tests\Exception;

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
class SemanticDataImportExceptionTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$instance = new SemanticDataImportException();

		$this->assertInstanceof(
			'\SMW\Exception\SemanticDataImportException',
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
