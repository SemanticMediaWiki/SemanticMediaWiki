<?php

namespace SMW\Tests\Schema\Exception;

use SMW\Schema\Exception\SchemaTypeAlreadyExistsException;

/**
 * @covers \SMW\Schema\Exception\SchemaTypeAlreadyExistsException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class SchemaTypeAlreadyExistsExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new SchemaTypeAlreadyExistsException( 'foo' );

		$this->assertInstanceof(
			SchemaTypeAlreadyExistsException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
