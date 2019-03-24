<?php

namespace SMW\Tests\Property\Constraint;

use SMW\Property\Constraint\ConstraintSchemaCompiler;
use SMW\DataItemFactory;
use SMW\Tests\TestEnvironment;
use SMWDIBlob as DIBlob;
use SMW\Message;

/**
 * @covers \SMW\Property\Constraint\ConstraintSchemaCompiler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintSchemaCompilerTest extends \PHPUnit_Framework_TestCase {

	private $schemaFinder;
	private $propertySpecificationLookup;

	protected function setUp() {
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

		$this->assertEquals(
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
			json_encode( $constraintSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES |JSON_UNESCAPED_UNICODE ),
			$instance->prettify( $constraintSchema )
		);
	}

	public function testCompileConstraintSchema_allowed_values() {

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedValues' )
			->will( $this->returnValue( [ new DIBlob( 'foo' ) ] ) );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedListValues' )
			->will( $this->returnValue( [ new DIBlob( 'bar' ) ] ) );

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$property->expects( $this->any() )
			->method( 'findPropertyValueType' )
			->will( $this->returnValue( '_foo' ) );

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
			->will( $this->returnValue( 'Foo' ) );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedValues' )
			->will( $this->returnValue( [] ) );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedListValues' )
			->will( $this->returnValue( [] ) );

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$property->expects( $this->any() )
			->method( 'findPropertyValueType' )
			->will( $this->returnValue( '_foo' ) );

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
			->will( $this->returnValue( true ) );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedValues' )
			->will( $this->returnValue( [] ) );

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getAllowedListValues' )
			->will( $this->returnValue( [] ) );

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$property->expects( $this->any() )
			->method( 'findPropertyValueType' )
			->will( $this->returnValue( '_foo' ) );

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
