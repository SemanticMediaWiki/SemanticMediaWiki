<?php

namespace SMW\Tests\Unit\DataValues;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataItems\Container;
use SMW\DataItems\Error;
use SMW\DataValues\ReferenceValue;
use SMW\DataValues\ValueValidators\ConstraintValueValidator;
use SMW\Property\SpecificationLookup;
use SMW\Services\DataValueServiceFactory;
use SMW\Store;

/**
 * @covers \SMW\DataValues\ReferenceValue
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class ReferenceValueTest extends TestCase {

	private DataItemFactory $dataItemFactory;
	private SpecificationLookup $propertySpecificationLookup;
	private DataValueServiceFactory $dataValueServiceFactory;
	private Store $store;

	protected function setUp(): void {
		parent::setUp();

		$this->dataItemFactory = new DataItemFactory();

		$this->propertySpecificationLookup = $this->getMockBuilder( SpecificationLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getRedirectTarget' ] )
			->getMockForAbstractClass();

		$this->store->expects( $this->any() )
			->method( 'getRedirectTarget' )
			->willReturnArgument( 0 );

		$constraintValueValidator = $this->getMockBuilder( ConstraintValueValidator::class )
			->getMock();

		$this->dataValueServiceFactory = $this->getMockBuilder( DataValueServiceFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getPropertySpecificationLookup' )
			->willReturn( $this->propertySpecificationLookup );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getStore' )
			->willReturn( $this->store );

		$this->dataValueServiceFactory->expects( $this->any() )
			->method( 'getConstraintValueValidator' )
			->willReturn( $constraintValueValidator );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ReferenceValue::class,
			new ReferenceValue()
		);
	}

	public function testGetPropertyDataItems() {
		$expected = [
			$this->dataItemFactory->newDIProperty( 'Bar' ),
			$this->dataItemFactory->newDIProperty( 'Foobar' )
		];

		$this->propertySpecificationLookup->expects( $this->atLeastOnce() )
			->method( 'getFieldListBy' )
			->willReturn( $this->dataItemFactory->newDIBlob( 'Bar;Foobar' ) );

		$instance = new ReferenceValue();
		$instance->setDataValueServiceFactory( $this->dataValueServiceFactory );
		$instance->setProperty(
			$this->dataItemFactory->newDIProperty( 'Foo' )
		);

		$this->assertEquals(
			$expected,
			$instance->getPropertyDataItems()
		);

		$this->assertEquals(
			$this->dataItemFactory->newDIProperty( 'Foobar' ),
			$instance->getPropertyDataItemByIndex( 'Foobar' )
		);
	}

	public function testParseValue() {
		$this->propertySpecificationLookup->expects( $this->atLeastOnce() )
			->method( 'getFieldListBy' )
			->willReturn( $this->dataItemFactory->newDIBlob( 'Bar;Foobar' ) );

		$instance = new ReferenceValue();
		$instance->setDataValueServiceFactory( $this->dataValueServiceFactory );
		$instance->setProperty(
			$this->dataItemFactory->newDIProperty( 'Foo' )
		);

		$instance->setUserValue( '123;abc' );
		$container = $instance->getDataItem();

		$this->assertInstanceOf(
			Container::class,
			$container
		);

		$semanticData = $container->getSemanticData();

		$this->assertTrue(
			$semanticData->hasProperty( $this->dataItemFactory->newDIProperty( 'Foobar' ) )
		);
	}

	public function testParseValueOnMissingValues() {
		$instance = new ReferenceValue();
		$instance->setProperty(
			$this->dataItemFactory->newDIProperty( 'Foo' )
		);

		$instance->setUserValue( '' );

		$this->assertInstanceOf(
			Error::class,
			$instance->getDataItem()
		);
	}

	public function testParseValueWithErroredDv() {
		$this->propertySpecificationLookup->expects( $this->atLeastOnce() )
			->method( 'getFieldListBy' )
			->willReturn( $this->dataItemFactory->newDIBlob( 'Bar;Foobar' ) );

		$instance = new ReferenceValue();
		$instance->setDataValueServiceFactory( $this->dataValueServiceFactory );
		$instance->setProperty(
			$this->dataItemFactory->newDIProperty( 'Foo' )
		);

		$instance->setUserValue( 'Foo;<>Foo' );

		$this->assertInstanceOf(
			Error::class,
			$instance->getDataItem()
		);

		$this->assertStringContainsString(
			"smw-datavalue-wikipage-property-invalid-title",
			implode( ' ', $instance->getErrors() )
		);
	}

	public function testGetValuesFromStringWithEncodedSemicolon() {
		$instance = new ReferenceValue();

		$this->assertEquals(
			[ 'abc', '1;2', 3 ],
			$instance->getValuesFromString( 'abc;1\;2;3' )
		);
	}

}
