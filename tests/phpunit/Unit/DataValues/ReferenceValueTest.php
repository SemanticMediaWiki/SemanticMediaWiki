<?php

namespace SMW\Tests\DataValues;

use SMW\DataItemFactory;
use SMW\DataValues\ReferenceValue;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\DataValues\ReferenceValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ReferenceValueTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dataItemFactory;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\PropertySpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\ReferenceValue',
			new ReferenceValue()
		);
	}

	public function testGetPropertyDataItems() {

		$expected = [
			$this->dataItemFactory->newDIProperty( 'Bar' ),
			$this->dataItemFactory->newDIProperty( 'Foobar' )
		];

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getRedirectTarget' ] )
			->getMockForAbstractClass();

		$this->propertySpecificationLookup->expects( $this->atLeastOnce() )
			->method( 'getFieldListBy' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( 'Bar;Foobar' ) ) );

		$store->expects( $this->any() )
			->method( 'getRedirectTarget' )
			->will( $this->returnArgument( 0 ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new ReferenceValue();
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

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getRedirectTarget' ] )
			->getMockForAbstractClass();

		$this->propertySpecificationLookup->expects( $this->atLeastOnce() )
			->method( 'getFieldListBy' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( 'Bar;Foobar' ) ) );

		$store->expects( $this->any() )
			->method( 'getRedirectTarget' )
			->will( $this->returnArgument( 0 ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new ReferenceValue();
		$instance->setProperty(
			$this->dataItemFactory->newDIProperty( 'Foo' )
		);

		$instance->setUserValue( '123;abc' );
		$container = $instance->getDataItem();

		$this->assertInstanceOf(
			'\SMWDIContainer',
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
			'\SMWDIError',
			$instance->getDataItem()
		);
	}

	public function testParseValueWithErroredDv() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( [ 'getRedirectTarget' ] )
			->getMockForAbstractClass();

		$this->propertySpecificationLookup->expects( $this->atLeastOnce() )
			->method( 'getFieldListBy' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( 'Bar;Foobar' ) ) );

		$store->expects( $this->any() )
			->method( 'getRedirectTarget' )
			->will( $this->returnArgument( 0 ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new ReferenceValue();
		$instance->setProperty(
			$this->dataItemFactory->newDIProperty( 'Foo' )
		);

		$instance->setUserValue( 'Foo;<>Foo' );

		$this->assertInstanceOf(
			'\SMWDIError',
			$instance->getDataItem()
		);

		$this->assertContains(
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
