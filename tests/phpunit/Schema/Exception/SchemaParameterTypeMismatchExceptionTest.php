<?php

namespace SMW\Tests\Schema\Exception;

use SMW\Schema\Exception\SchemaParameterTypeMismatchException;

/**
 * @covers \SMW\Schema\Exception\SchemaParameterTypeMismatchException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class SchemaParameterTypeMismatchExceptionTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$instance = new SchemaParameterTypeMismatchException( 'foo', 'array' );

		$this->assertInstanceof(
			SchemaParameterTypeMismatchException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
