<?php

namespace SMW\Tests\Schema;

use SMW\DataItemFactory;
use SMW\Schema\SchemaValidator;
use SMW\Schema\SchemaDefinition;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\Schema\SchemaValidator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaValidatorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$jsonSchemaValidator = $this->getMockBuilder( '\SMW\Utils\JsonSchemaValidator' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			SchemaValidator::class,
			new SchemaValidator( $jsonSchemaValidator )
		);
	}

	public function testValidate_IsValid() {

		$jsonSchemaValidator = $this->getMockBuilder( '\SMW\Utils\JsonSchemaValidator' )
			->disableOriginalConstructor()
			->getMock();

		$jsonSchemaValidator->expects( $this->once() )
			->method( 'validate' )
			->will( $this->returnValue( false ) );

		$jsonSchemaValidator->expects( $this->once() )
			->method( 'isValid' )
			->will( $this->returnValue( true ) );

		$instance = new SchemaValidator( $jsonSchemaValidator );

		$this->assertEmpty(
			$instance->validate( new SchemaDefinition( 'foo', [], '...' ) )
		);
	}

	public function testValidate_Error() {

		$jsonSchemaValidator = $this->getMockBuilder( '\SMW\Utils\JsonSchemaValidator' )
			->disableOriginalConstructor()
			->getMock();

		$jsonSchemaValidator->expects( $this->once() )
			->method( 'validate' )
			->will( $this->returnValue( false ) );

		$jsonSchemaValidator->expects( $this->once() )
			->method( 'isValid' )
			->will( $this->returnValue( false ) );

		$jsonSchemaValidator->expects( $this->once() )
			->method( 'getErrors' )
			->will( $this->returnValue( [ '...' ] ) );

		$instance = new SchemaValidator( $jsonSchemaValidator );

		$this->assertNotEmpty(
			$instance->validate( new SchemaDefinition( 'foo', [], '...' ) )
		);
	}

}
