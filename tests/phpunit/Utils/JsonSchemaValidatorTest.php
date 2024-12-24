<?php

namespace SMW\Tests\Utils;

use JsonSchema\Exception\ResourceNotFoundException;
use JsonSchema\Validator as SchemaValidator;
use JsonSerializable;
use SMW\Services\ServicesFactory as ApplicationFactory;
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
class JsonSchemaValidatorTest extends \PHPUnit\Framework\TestCase {

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
			->onlyMethods( [ 'jsonSerialize' ] )
			->getMock();

		$data->expects( $this->any() )
			->method( 'jsonSerialize' )
			->willReturn( json_encode( [ 'Foo' ] ) );

		$schemaValidator = $this->getMockBuilder( SchemaValidator::class )
			->onlyMethods( [ 'check' ] )
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
			->onlyMethods( [ 'jsonSerialize' ] )
			->getMock();

		$data->expects( $this->any() )
			->method( 'jsonSerialize' )
			->willReturn( json_encode( [ 'Foo' ] ) );

		$schemaValidator = $this->getMockBuilder( SchemaValidator::class )
			->onlyMethods( [ 'check' ] )
			->getMock();

		$schemaValidator->expects( $this->any() )
			->method( 'check' )
			->willThrowException( new ResourceNotFoundException() );

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

	public function testNoJSONValidatorButSchemaLink() {
		$instance = new JsonSchemaValidator();

		$instance->validate( $this->newJsonSerializable( [] ), 'Foo' );

		$this->assertFalse(
			$instance->isValid()
		);

		$this->assertNotEmpty(
			$instance->getErrors()
		);
	}

	public function newJsonSerializable( $data ) {
		return new class( $data ) implements \JsonSerializable {

			private $data;

			public function __construct( $data ) {
				$this->data = $data;
			}

			public function jsonSerialize(): string {
				return json_encode( $this->data );
			}
		};
	}
}
