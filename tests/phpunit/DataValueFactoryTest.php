<?php

namespace SMW\Tests;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataValueFactory;
use SMW\DataValues\DataValue;
use SMW\DataValues\ErrorValue;
use SMW\DataValues\NumberValue;
use SMW\DataValues\PropertyValue;
use SMW\DataValues\StringValue;
use SMW\DataValues\TimeValue;
use SMW\DataValues\WikiPageValue;
use SMW\DataValues\URIValue;

/**
 * @covers \SMW\DataValueFactory
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class DataValueFactoryTest extends TestCase {

	protected function tearDown(): void {
		DataValueFactory::getInstance()->clear();
		parent::tearDown();
	}

	public function testAddGetCallable() {
		$dataValueFactory = DataValueFactory::getInstance();

		$test = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'doRun' ] )
			->getMock();

		$test->expects( $this->once() )
			->method( 'doRun' );

		$callback = static function () use( $test ) {
			return $test;
		};

		$dataValueFactory->addCallable( 'foo.test', $callback );

		$dataValue = $dataValueFactory->newTypeIdValue(
			'_txt',
			'foo'
		);

		$callback = $dataValue->getCallable( 'foo.test' );
		$callback()->doRun();
	}

	public function testAddCallableOnAlreadyRegisteredKeyThrowsException() {
		$dataValueFactory = DataValueFactory::getInstance();

		$dataValueFactory->addCallable( 'foo.test', [ $this, 'testAddCallableOnAlreadyRegisteredKeyThrowsException' ] );

		$this->expectException( '\RuntimeException' );
		$dataValueFactory->addCallable( 'foo.test', [ $this, 'testAddCallableOnAlreadyRegisteredKeyThrowsException' ] );

		$dataValueFactory->clearCallable( 'foo.test' );
	}

	public function testGetCallableOnUnknownKeyThrowsException() {
		$dataValueFactory = DataValueFactory::getInstance();

		$dataValue = $dataValueFactory->newTypeIdValue(
			'_txt',
			'foo'
		);

		$this->expectException( '\RuntimeException' );
		$dataValue->getCallable( 'foo.test' );
	}

	/**
	 * @dataProvider typeIdValueDataProvider
	 */
	public function testNewTypeIdValue( $typeId, $value, $expectedValue, $expectedInstance ) {
		$dataValue = DataValueFactory::getInstance()->newTypeIdValue( $typeId, $value );

		$this->assertInstanceOf(
			$expectedInstance,
			$dataValue
		);

		if ( $dataValue->getErrors() === [] ) {
			return $this->assertEquals(
				$expectedValue,
				$dataValue->getWikiValue()
			);
		}

		$this->assertIsArray(

			$dataValue->getErrors()
		);
	}

	/**
	 * @dataProvider propertyObjectValueDataProvider
	 */
	public function testNewPropertyObjectValue( $propertyName, $value, $expectedValue, $expectedInstance ) {
		$propertyDV = DataValueFactory::getInstance()->newPropertyValueByLabel( $propertyName );
		$propertyDI = $propertyDV->getDataItem();

		$dataValue = DataValueFactory::getInstance()->newDataValueByProperty( $propertyDI, $value );

		// Check the returned instance
		$this->assertInstanceOf( $expectedInstance, $dataValue );

		if ( $dataValue->getErrors() === [] ) {
			$this->assertInstanceOf( Property::class, $dataValue->getProperty() );
			$this->assertStringContainsString( $propertyName, $dataValue->getProperty()->getLabel() );
			if ( $dataValue->getDataItem()->getDIType() === DataItem::TYPE_WIKIPAGE ) {
				$this->assertEquals( $expectedValue, $dataValue->getWikiValue() );
			}
		} else {
			$this->assertIsArray( $dataValue->getErrors() );
		}

		// Check interface parameters
		$dataValue = DataValueFactory::getInstance()->newDataValueByProperty(
			$propertyDI,
			$value,
			'FooCaption',
			new WikiPage( 'Foo', NS_MAIN )
		);

		$this->assertInstanceOf(
			$expectedInstance,
			$dataValue
		);
	}

	/**
	 * @dataProvider propertyValueDataProvider
	 */
	public function testAddPropertyValueByText( $propertyName, $value, $expectedValue, $expectedInstance ) {
		$dataValue = DataValueFactory::getInstance()->newDataValueByText( $propertyName, $value );

		// Check the returned instance
		$this->assertInstanceOf( $expectedInstance, $dataValue );

		if ( $dataValue->getErrors() === [] ) {
			$this->assertInstanceOf( Property::class, $dataValue->getProperty() );
			$this->assertStringContainsString( $propertyName, $dataValue->getProperty()->getLabel() );
			if ( $dataValue->getDataItem()->getDIType() === DataItem::TYPE_WIKIPAGE ) {
				$this->assertEquals( $expectedValue, $dataValue->getWikiValue() );
			}
		} else {
			$this->assertIsArray( $dataValue->getErrors() );
		}

		// Check interface parameters
		$dataValue = DataValueFactory::getInstance()->newDataValueByText(
			$propertyName,
			$value,
			'FooCaption',
			new WikiPage( 'Foo', NS_MAIN )
		);

		$this->assertInstanceOf(
			$expectedInstance,
			$dataValue
		);
	}

	public function testTryToCreateDataValueUsingRestrictedPropertyValue() {
		$dataValue = DataValueFactory::getInstance()->newDataValueByText( 'Has subobject', 'Foo' );

		$this->assertInstanceOf(
			ErrorValue::class,
			$dataValue
		);

		$this->assertNotEmpty(
			$dataValue->getErrors()
		);
	}

	public function testToCreateDataValueUsingLegacyNewPropertyValueMethod() {
		$dataValue = DataValueFactory::getInstance()->newPropertyValue( 'Bar', 'Foo' );

		$this->assertInstanceOf(
			DataValue::class,
			$dataValue
		);
	}

	/**
	 * Issue 673
	 */
	public function testEnforceFirstUpperCaseForDisabledCapitalLinks() {
		$wgCapitalLinks = $GLOBALS['wgCapitalLinks'];
		$GLOBALS['wgCapitalLinks'] = false;

		$instance = DataValueFactory::getInstance();

		$dataValue = $instance->newDataValueByText(
			'has type',
			'number',
			null,
			new WikiPage( 'Foo', SMW_NS_PROPERTY )
		);

		$this->assertEquals(
			'_TYPE',
			$dataValue->getProperty()->getKey()
		);

		$GLOBALS['wgCapitalLinks'] = $wgCapitalLinks;
	}

	public function testNewPropertyValueByLabel() {
		$dataValue = DataValueFactory::getInstance()->newPropertyValueByLabel(
			'Foo',
			'Bar',
			new WikiPage( 'Foobar', SMW_NS_PROPERTY )
		);

		$this->assertInstanceOf(
			PropertyValue::class,
			$dataValue
		);

		$this->assertSame(
			'Bar',
			$dataValue->getCaption()
		);
	}

	public function testNewPropertyValueByItem() {
		$dataValue = DataValueFactory::getInstance()->newPropertyValueByItem(
			Property::newFromUserLabel( __METHOD__ ),
			'Bar',
			new WikiPage( 'Foobar', SMW_NS_PROPERTY )
		);

		$this->assertInstanceOf(
			PropertyValue::class,
			$dataValue
		);

		$this->assertSame(
			'Bar',
			$dataValue->getCaption()
		);
	}

	/**
	 * @dataProvider newDataValueByItemDataProvider
	 */
	public function testNewDataItemValue( $setup ) {
		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$setup['dataItem'],
			$setup['property'],
			$setup['caption']
		);

		$this->assertInstanceOf(
			DataValue::class,
			$dataValue
		);
	}

	public function newDataValueByItemDataProvider() {
		$provider = [];

		$dataItem = new WikiPage( 'Foo', NS_MAIN );
		$property = new Property( 'Bar' );

		// #0
		$provider[] = [
			[
				'dataItem' => $dataItem,
				'property' => null,
				'caption'  => false
			]
		];

		// #0
		$provider[] = [
			[
				'dataItem' => $dataItem,
				'property' => $property,
				'caption'  => false
			]
		];

		// #1
		$provider[] = [
			[
				'dataItem' => $dataItem,
				'property' => null,
				'caption'  => 'Foo'
			]
		];

		// #2
		$provider[] = [
			[
				'dataItem' => $dataItem,
				'property' => $property,
				'caption'  => 'Bar'
			]
		];

		return $provider;
	}

	public function findTypeIdDataProvider() {
		return [
			[ 'URL', '_uri' ], // #0
			[ 'Page', '_wpg' ], // #1
			[ 'String', '_txt' ], // #2
			[ 'Text', '_txt' ], // #3
			[ 'Number', '_num' ], // #4
			[ 'Quantity', '_qty' ], // #5
			[ 'Date', '_dat' ], // #6
			[ 'Email', '_ema' ], // #7
			[ '', '' ], // #8
		];
	}

	public function dataItemIdDataProvider() {
		return [
			[ '_txt', DataItem::TYPE_BLOB ], // #0
			[ '_wpg', DataItem::TYPE_WIKIPAGE ], // #1
			[ '_num', DataItem::TYPE_NUMBER ], // #2
			[ '_dat', DataItem::TYPE_TIME ], // #3
			[ '_uri', DataItem::TYPE_URI ], // #4
			[ '_foo', DataItem::TYPE_NOTYPE ], // #5
		];
	}

	public function typeIdValueDataProvider() {
		return [
			[ '_txt', 'Bar', 'Bar', StringValue::class ], // #0
			[ '_txt', 'Bar[[ Foo ]]', 'Bar[[ Foo ]]', StringValue::class ], // #1
			[ '_txt', '9001', '9001', StringValue::class ], // #2
			[ '_txt', 1001, '1001', StringValue::class ], // #3
			[ '_txt', '-%&$*', '-%&$*', StringValue::class ], // #4
			[ '_txt', '_Bar', '_Bar', StringValue::class ], // #5
			[ '_txt', 'bar', 'bar', StringValue::class ], // #6
			[ '-_txt', 'Bar', 'Bar', ErrorValue::class ], // #7

			[ '_wpg', 'Bar', 'Bar', WikiPageValue::class ], // #8
			[ '_wpg', 'Bar', 'Bar', WikiPageValue::class ], // #9
			[ '_wpg', 'Bar[[ Foo ]]', 'Bar[[ Foo ]]', ikiPageValue::class ], // #10
			[ '_wpg', '9001', '9001', WikiPageValue::class ], // #11
			[ '_wpg', 1001, '1001', WikiPageValue::class ], // #12
			[ '_wpg', '-%&$*', '-%&$*', WikiPageValue::class ], // #13
			[ '_wpg', '_Bar', 'Bar', WikiPageValue::class ], // #14
			[ '_wpg', 'bar', 'Bar', WikiPageValue::class ], // #15
			[ '-_wpg', 'Bar', 'Bar', ErrorValue::class ], // #16

			[ '_dat', '1 Jan 1970', '1 Jan 1970', TimeValue::class ], // #0
			[ '_uri', 'Foo', 'Foo', URIValue::class ], // #0
			[ '_num', 9001, '9001', NumberValue::class ], // #0
			[ '_num', 9001.5, '9001.5', NumberValue::class ], // #0
		];
	}

	public function propertyValueDataProvider() {
		return [
			[ 'Foo', 'Bar', 'Bar', DataValue::class ], // #0
			[ 'Foo', 'Bar[[ Foo ]]', 'Bar[[ Foo ]]', DataValue::class ], // #1
			[ 'Foo', '9001', '9001', DataValue::class ], // #2
			[ 'Foo', 1001, '1001', DataValue::class ], // #3
			[ 'Foo', '-%&$*', '-%&$*', DataValue::class ], // #4
			[ 'Foo', '_Bar', 'Bar', DataValue::class ], // #5
			[ 'Foo', 'bar', 'Bar', DataValue::class ], // #6
			[ '-Foo', 'Bar', '', ErrorValue::class ], // #7
			[ '_Foo', 'Bar', '', PropertyValue::class ], // #8
		];
	}

	/**
	 * @return array
	 */
	public function propertyObjectValueDataProvider() {
		return [
			[ 'Foo', 'Bar', 'Bar', DataValue::class ], // #0
			[ 'Foo', 'Bar[[ Foo ]]', 'Bar[[ Foo ]]', DataValue::class ], // #1
			[ 'Foo', '9001', '9001', DataValue::class ], // #2
			[ 'Foo', 1001, '1001', DataValue::class ], // #3
			[ 'Foo', '-%&$*', '-%&$*', DataValue::class ], // #4
			[ 'Foo', '_Bar', 'Bar', DataValue::class ], // #5
			[ 'Foo', 'bar', 'Bar', DataValue::class ], // #6
			[ '-Foo', 'Bar', 'Bar', WikiPageValue::class ], // #7

			// Will fail with "must be an instance of \SMW\DataItems\Property, instance of Error give"
			// as propertyDI isn't checked therefore addPropertyValue() should be
			// used as it will return a proper object
			// array( '_Foo' , 'Bar'          , ''             , '\SMW\DataItems\Property' ), // #8
		];
	}

}
