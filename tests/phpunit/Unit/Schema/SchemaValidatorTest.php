<?php

namespace SMW\Tests\Unit\Schema;

use JsonSchema\Exception\ResourceNotFoundException;
use JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use SMW\Schema\SchemaDefinition;
use SMW\Schema\SchemaValidator;

/**
 * @covers \SMW\Schema\SchemaValidator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class SchemaValidatorTest extends TestCase {

	public function testCanConstruct() {
		$validator = $this->createMock( Validator::class );

		$this->assertInstanceOf(
			SchemaValidator::class,
			new SchemaValidator( $validator )
		);
	}

	public function testValidate_InaccessibleFile() {
		$validator = $this->createMock( Validator::class );

		$validator->expects( $this->never() )
			->method( 'check' );

		$instance = new SchemaValidator( $validator );

		$info = [
			SchemaDefinition::SCHEMA_VALIDATION_FILE => '...'
		];

		$this->assertNotEmpty(
			$instance->validate( new SchemaDefinition( 'foo', [], $info ) )
		);
	}

	public function testValidate_IsValid() {
		$validator = $this->createMock( Validator::class );

		$validator->expects( $this->once() )
			->method( 'check' );

		$validator->expects( $this->once() )
			->method( 'isValid' )
			->willReturn( true );

		$instance = new SchemaValidator( $validator );

		$info = [
			SchemaDefinition::SCHEMA_VALIDATION_FILE => \SMW_PHPUNIT_DIR . '/Fixtures/Schema/empty_schema.json'
		];

		$this->assertEmpty(
			$instance->validate( new SchemaDefinition( 'foo', [], $info ) )
		);
	}

	public function testValidate_Error() {
		$validator = $this->createMock( Validator::class );

		$validator->expects( $this->once() )
			->method( 'check' );

		$validator->expects( $this->once() )
			->method( 'isValid' )
			->willReturn( false );

		$validator->expects( $this->once() )
			->method( 'getErrors' )
			->willReturn( [ '...' ] );

		$instance = new SchemaValidator( $validator );

		$info = [
			SchemaDefinition::SCHEMA_VALIDATION_FILE => \SMW_PHPUNIT_DIR . '/Fixtures/Schema/empty_schema.json'
		];

		$this->assertNotEmpty(
			$instance->validate( new SchemaDefinition( 'foo', [], $info ) )
		);
	}

	public function testValidate_ResourceNotFoundException() {
		$validator = $this->createMock( Validator::class );

		$validator->expects( $this->once() )
			->method( 'check' )
			->willThrowException( new ResourceNotFoundException() );

		$instance = new SchemaValidator( $validator );

		$info = [
			SchemaDefinition::SCHEMA_VALIDATION_FILE => \SMW_PHPUNIT_DIR . '/Fixtures/Schema/empty_schema.json'
		];

		$this->assertNotEmpty(
			$instance->validate( new SchemaDefinition( 'foo', [], $info ) )
		);
	}

	public function testValidate_EmptySchema() {
		$validator = $this->createMock( Validator::class );

		$validator->expects( $this->never() )
			->method( 'check' );

		$instance = new SchemaValidator( $validator );

		$this->assertEquals(
			[],
			$instance->validate( null )
		);
	}

}
