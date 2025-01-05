<?php

namespace SMW\Tests\DataValues;

use SMW\DataItemFactory;
use SMW\DataValues\ConstraintSchemaValue;
use SMW\DIWikiPage;
use SMW\PropertySpecificationLookup;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\ConstraintSchemaValue
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintSchemaValueTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $dataItemFactory;
	private $propertySpecificationLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->propertySpecificationLookup = $this->getMockBuilder( PropertySpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	}

	protected function tearDown(): void {
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
			->willReturn( [] );

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
				$this->dataItemFactory->newDIProperty( '_SCHEMA_DEF' ) )
			->willReturn( [ $this->dataItemFactory->newDIBlob( $data ) ] );

		$instance = new ConstraintSchemaValue(
			ConstraintSchemaValue::TYPE_ID,
			$this->propertySpecificationLookup
		);

		$instance->setContextPage(
			DIWikiPage::newFromText( 'Foo', SMW_NS_PROPERTY )
		);

		$instance->setUserValue( 'Foo' );

		$this->assertContains(
			'[2,"smw-constraint-schema-property-invalid-type","Foo","PROPERTY_CONSTRAINT_SCHEMA"]',
			implode( ',', $instance->getErrors() )
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
				$this->dataItemFactory->newDIProperty( '_SCHEMA_DEF' ) )
			->willReturn( [ $this->dataItemFactory->newDIBlob( $data ) ] );

		$instance = new ConstraintSchemaValue(
			ConstraintSchemaValue::TYPE_ID,
			$this->propertySpecificationLookup
		);

		$instance->setContextPage(
			DIWikiPage::newFromText( 'Foo', NS_CATEGORY )
		);

		$instance->setUserValue( 'Foo' );

		$this->assertContains(
			'[2,"smw-constraint-schema-category-invalid-type","Foo","CLASS_CONSTRAINT_SCHEMA"]',
			implode( ',', $instance->getErrors() )
		);
	}

}
