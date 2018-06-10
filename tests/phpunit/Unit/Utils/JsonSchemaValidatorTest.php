<?php

namespace SMW\Tests\Utils;

use JsonSchema\Exception\ResourceNotFoundException;
use JsonSchema\Validator as SchemaValidator;
use JsonSerializable;
use SMW\ApplicationFactory;
use SMW\Utils\JsonSchemaValidator;

/**
 * @covers \SMW\Utils\JsonSchemaValidator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class JsonSchemaValidatorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			JsonSchemaValidator::class,
			new JsonSchemaValidator()
		);

		$applicationFactory = ApplicationFactory::getInstance();

		$this->assertInstanceOf(
			JsonSchemaValidator::class,
			$applicationFactory->create( 'JsonSchemaValidator' )
		);

		$this->assertInstanceOf(
			JsonSchemaValidator::class,
			$applicationFactory->create( JsonSchemaValidator::class )
		);
	}

	public function testNoSchemaValidator() {

		$instance = new JsonSchemaValidator();

		$this->assertFalse(
			$instance->hasSchemaValidator()
		);
	}

	public function testValidate() {

		if ( !class_exists( SchemaValidator::class ) ) {
			$this->markTestSkipped( 'JsonSchema\Validator is not available.' );
		}

		$data = $this->getMockBuilder( JsonSerializable::class )
			->setMethods( [ 'jsonSerialize' ] )
			->getMock();

		$data->expects( $this->any() )
			->method( 'jsonSerialize' )
			->will( $this->returnValue( json_encode( [ 'Foo' ] ) ) );

		$schemaValidator = $this->getMockBuilder( SchemaValidator::class )
			->setMethods( [ 'check' ] )
			->getMock();

		$instance = new JsonSchemaValidator(
			$schemaValidator
		);

		$instance->validate( $data, 'Foo' );

		$this->assertTrue(
			$instance->isValid()
		);

		$this->assertEmpty(
			$instance->getErrors()
		);
	}

	public function testValidateWhereSchemaValidatorThrowsException() {

		if ( !class_exists( SchemaValidator::class ) ) {
			$this->markTestSkipped( 'JsonSchema\Validator is not available.' );
		}

		$data = $this->getMockBuilder( JsonSerializable::class )
			->setMethods( [ 'jsonSerialize' ] )
			->getMock();

		$data->expects( $this->any() )
			->method( 'jsonSerialize' )
			->will( $this->returnValue( json_encode( [ 'Foo' ] ) ) );

		$schemaValidator = $this->getMockBuilder( SchemaValidator::class )
			->setMethods( [ 'check' ] )
			->getMock();

		$schemaValidator->expects( $this->any() )
			->method( 'check' )
			->will($this->throwException( new ResourceNotFoundException() ) );

		$instance = new JsonSchemaValidator(
			$schemaValidator
		);

		$instance->validate( $data, 'Foo' );

		$this->assertFalse(
			$instance->isValid()
		);

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

}
