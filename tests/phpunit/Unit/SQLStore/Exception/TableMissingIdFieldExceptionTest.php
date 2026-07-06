<?php

namespace SMW\Tests\Unit\SQLStore\Exception;

use PHPUnit\Framework\TestCase;
use SMW\SQLStore\Exception\TableMissingIdFieldException;

/**
 * @covers \SMW\SQLStore\Exception\TableMissingIdFieldException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class TableMissingIdFieldExceptionTest extends TestCase {

	public function testCanConstruct() {
		$instance = new TableMissingIdFieldException( 'foo' );

		$this->assertInstanceof(
			TableMissingIdFieldException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
