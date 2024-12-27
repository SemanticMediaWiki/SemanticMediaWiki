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
class SchemaValidatorTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$jsonSchemaValidator = $this->getMockBuilder( '\SMW\Utils\JsonSchemaValidator' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			SchemaValidator::class,
			new SchemaValidator( $jsonSchemaValidator )
		);
	}

	public function testValidate_InaccessibleFile() {
		$jsonSchemaValidator = $this->getMockBuilder( '\SMW\Utils\JsonSchemaValidator' )
			->disableOriginalConstructor()
			->getMock();

		$jsonSchemaValidator->expects( $this->never() )
			->method( 'validate' )
			->willReturn( false );

		$instance = new SchemaValidator( $jsonSchemaValidator );

		$info = [
			SchemaDefinition::SCHEMA_VALIDATION_FILE => '...'
		];

		$this->assertNotEmpty(
			$instance->validate( new SchemaDefinition( 'foo', [], $info ) )
		);
	}

	public function testValidate_IsValid() {
		$jsonSchemaValidator = $this->getMockBuilder( '\SMW\Utils\JsonSchemaValidator' )
			->disableOriginalConstructor()
			->getMock();

		$jsonSchemaValidator->expects( $this->once() )
			->method( 'validate' )
			->willReturn( false );

		$jsonSchemaValidator->expects( $this->once() )
			->method( 'isValid' )
			->willReturn( true );

		$instance = new SchemaValidator( $jsonSchemaValidator );

		$info = [
			SchemaDefinition::SCHEMA_VALIDATION_FILE => \SMW_PHPUNIT_DIR . '/Fixtures/Schema/empty_schema.json'
		];

		$this->assertEmpty(
			$instance->validate( new SchemaDefinition( 'foo', [], $info ) )
		);
	}

	public function testValidate_Error() {
		$jsonSchemaValidator = $this->getMockBuilder( '\SMW\Utils\JsonSchemaValidator' )
			->disableOriginalConstructor()
			->getMock();

		$jsonSchemaValidator->expects( $this->once() )
			->method( 'validate' )
			->willReturn( false );

		$jsonSchemaValidator->expects( $this->once() )
			->method( 'isValid' )
			->willReturn( false );

		$jsonSchemaValidator->expects( $this->once() )
			->method( 'getErrors' )
			->willReturn( [ '...' ] );

		$instance = new SchemaValidator( $jsonSchemaValidator );

		$info = [
			SchemaDefinition::SCHEMA_VALIDATION_FILE => \SMW_PHPUNIT_DIR . '/Fixtures/Schema/empty_schema.json'
		];

		$this->assertNotEmpty(
			$instance->validate( new SchemaDefinition( 'foo', [], $info ) )
		);
	}

	public function testValidate_EmptySchema() {
		$jsonSchemaValidator = $this->getMockBuilder( '\SMW\Utils\JsonSchemaValidator' )
			->disableOriginalConstructor()
			->getMock();

		$jsonSchemaValidator->expects( $this->never() )
			->method( 'validate' )
			->willReturn( false );

		$instance = new SchemaValidator( $jsonSchemaValidator );

		$this->assertEquals(
			[],
			$instance->validate( null )
		);
	}

}
