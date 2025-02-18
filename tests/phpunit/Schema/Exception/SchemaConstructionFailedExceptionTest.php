<?php

namespace SMW\Tests\Schema\Exception;

use SMW\Schema\Exception\SchemaConstructionFailedException;

/**
 * @covers \SMW\Schema\Exception\SchemaConstructionFailedException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaConstructionFailedExceptionTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$instance = new SchemaConstructionFailedException( 'foo' );

		$this->assertInstanceof(
			SchemaConstructionFailedException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
