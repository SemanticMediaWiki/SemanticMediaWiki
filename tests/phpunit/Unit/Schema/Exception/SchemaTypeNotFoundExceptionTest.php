<?php

namespace SMW\Tests\Schema\Exception;

use SMW\Schema\Exception\SchemaTypeNotFoundException;

/**
 * @covers \SMW\Schema\Exception\SchemaTypeNotFoundException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaTypeNotFoundExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new SchemaTypeNotFoundException( 'foo' );

		$this->assertInstanceof(
			SchemaTypeNotFoundException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
