<?php

namespace SMW\Tests\DataValues;

use SMW\DataItemFactory;
use SMW\DataValueFactory;
use SMW\DataValues\ConstraintSchemaValue;
use SMW\PropertySpecificationLookup;
use SMW\DIWikiPage;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\ConstraintSchemaValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintSchemaValueTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dataItemFactory;
	private $propertySpecificationLookup;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->propertySpecificationLookup = $this->getMockBuilder( PropertySpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ConstraintSchemaValue::class,
			new ConstraintSchemaValue( ConstraintSchemaValue::TYPE_ID, $this->propertySpecificationLookup )
		);
	}

	public function testNoSchema() {

		$this->propertySpecificationLookup->expects( $this->any() )
			->method( 'getSpecification' )
			->will( $this->returnValue( [] ) );

		$instance = new ConstraintSchemaValue(
			ConstraintSchemaValue::TYPE_ID,
			$this->propertySpecificationLookup
		);

		$instance->setContextPage(
			DIWikiPage::newFromText( 'Foo', SMW_NS_PROPERTY )
		);

		$instance->setProperty( $this->dataItemFactory->newDIProperty( 'Bar' ) );
		$instance->setUserValue( 'Foo' );

		$this->assertEmpty(
			$instance->getErrors()
		);
	}

	public function testInvalidSchemaType_PropertyNamespace() {

		$data = json_encode(
			[
				'type' => 'CLASS_CONSTRAINT_SCHEMA'
			]
		);

		$this->propertySpecificationLookup->expects( $this->once() )
			->method( 'getSpecification' )
			->with(
				$this->anything(),
				$this->equalTo( $this->dataItemFactory->newDIProperty( '_SCHEMA_DEF' ) ) )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIBlob( $data ) ] ) );

		$instance = new ConstraintSchemaValue(
			ConstraintSchemaValue::TYPE_ID,
			$this->propertySpecificationLookup
		);

		$instance->setContextPage(
			DIWikiPage::newFromText( 'Foo', SMW_NS_PROPERTY )
		);

		$instance->setUserValue( 'Foo' );

		$this->assertContains(
			'[2,"smw-datavalue-constraint-schema-property-invalid-type","Foo","PROPERTY_CONSTRAINT_SCHEMA"]',
			implode(',', $instance->getErrors() )
		);
	}

	public function testInvalidSchemaType_CategoryNamespace() {

		$data = json_encode(
			[
				'type' => 'PROPERTY_CONSTRAINT_SCHEMA'
			]
		);

		$this->propertySpecificationLookup->expects( $this->once() )
			->method( 'getSpecification' )
			->with(
				$this->anything(),
				$this->equalTo( $this->dataItemFactory->newDIProperty( '_SCHEMA_DEF' ) ) )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIBlob( $data ) ] ) );

		$instance = new ConstraintSchemaValue(
			ConstraintSchemaValue::TYPE_ID,
			$this->propertySpecificationLookup
		);

		$instance->setContextPage(
			DIWikiPage::newFromText( 'Foo', NS_CATEGORY )
		);

		$instance->setUserValue( 'Foo' );

		$this->assertContains(
			'[2,"smw-datavalue-constraint-schema-category-invalid-type","Foo","CLASS_CONSTRAINT_SCHEMA"]',
			implode(',', $instance->getErrors() )
		);
	}

}
