<?php

namespace SMW\Tests\Constraint;

use SMW\Constraint\ConstraintSchemaCompiler;
use SMW\DataItemFactory;
use SMW\Tests\TestEnvironment;
use SMWDIBlob as DIBlob;
use SMW\Message;

/**
 * @covers \SMW\Constraint\ConstraintSchemaCompiler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintSchemaCompilerTest extends \PHPUnit\Framework\TestCase {

	private $schemaFinder;
	private $propertySpecificationLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->schemaFinder = $this->getMockBuilder( '\SMW\Schema\SchemaFinder' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ConstraintSchemaCompiler::class,
			new ConstraintSchemaCompiler( $this->schemaFinder, $this->propertySpecificationLookup )
		);
	}

	public function testPrettifyOnEmpty() {
		$instance = new ConstraintSchemaCompiler(
			$this->schemaFinder,
			$this->propertySpecificationLookup
		);

		$this->assertSame(
			'',
			$instance->prettify( [] )
		);
	}

	public function testPrettifyAsJSON() {
		$instance = new ConstraintSchemaCompiler(
			$this->schemaFinder,
			$this->propertySpecificationLookup
		);

		$constraintSchema = [
			'type' => 'PROPERTY_CONSTRAINT_SCHEMA',
			'constraints' => [
				'type_constraint' => '_foo',
				'allowed_values' => [ 'foo', 'bar' ]
			]
		];

		$this->assertSame(
			json_encode( $constraintSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
			$instance->prettify( $constraintSchema )
		);
	}

	public function testCompileConstraintSchema_allowed_values() {
		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedValues' )
			->willReturn( [ new DIBlob( 'foo' ) ] );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedListValues' )
			->willReturn( [ new DIBlob( 'bar' ) ] );

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$property->expects( $this->any() )
			->method( 'findPropertyValueType' )
			->willReturn( '_foo' );

		$instance = new ConstraintSchemaCompiler(
			$this->schemaFinder,
			$this->propertySpecificationLookup
		);

		$expected = [
			'type' => 'PROPERTY_CONSTRAINT_SCHEMA',
			'constraints' => [
				'type_constraint' => '_foo',
				'allowed_values' => [ 'foo', 'bar' ]
			]
		];

		$this->assertEquals(
			$expected,
			$instance->compileConstraintSchema( $property )
		);
	}

	public function testCompileConstraintSchema_allowed_pattern() {
		$hash = Message::getHash(
			[ 'smw_allows_pattern' ],
			Message::TEXT,
			Message::CONTENT_LANGUAGE
		);

		$message = Message::getCache()->save( $hash, "...\nFoo|Bar...pattern" );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedPatternBy' )
			->willReturn( 'Foo' );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedValues' )
			->willReturn( [] );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedListValues' )
			->willReturn( [] );

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$property->expects( $this->any() )
			->method( 'findPropertyValueType' )
			->willReturn( '_foo' );

		$instance = new ConstraintSchemaCompiler(
			$this->schemaFinder,
			$this->propertySpecificationLookup
		);

		$expected = [
			'type' => 'PROPERTY_CONSTRAINT_SCHEMA',
			'constraints' => [
				'type_constraint' => '_foo',
				'allowed_pattern' => [ 'Foo' => 'Bar...pattern' ]
			]
		];

		$this->assertEquals(
			$expected,
			$instance->compileConstraintSchema( $property )
		);

		Message::clear();
	}

	public function testCompileConstraintSchema_unique_value_constraint() {
		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'hasUniquenessConstraint' )
			->willReturn( true );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedValues' )
			->willReturn( [] );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedListValues' )
			->willReturn( [] );

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$property->expects( $this->any() )
			->method( 'findPropertyValueType' )
			->willReturn( '_foo' );

		$instance = new ConstraintSchemaCompiler(
			$this->schemaFinder,
			$this->propertySpecificationLookup
		);

		$expected = [
			'type' => 'PROPERTY_CONSTRAINT_SCHEMA',
			'constraints' => [
				'type_constraint' => '_foo',
				'unique_value_constraint' => true
			]
		];

		$this->assertEquals(
			$expected,
			$instance->compileConstraintSchema( $property )
		);
	}

}
