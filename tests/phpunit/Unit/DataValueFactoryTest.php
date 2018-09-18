<?php

namespace SMW\Tests;

use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMWDataItem;

/**
 * @covers \SMW\DataValueFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class DataValueFactoryTest extends \PHPUnit_Framework_TestCase {

	protected function tearDown() {
		DataValueFactory::getInstance()->clear();
		parent::tearDown();
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

		if ( $dataValue->getErrors() === [] ){
			return $this->assertEquals(
				$expectedValue,
				$dataValue->getWikiValue()
			);
		}

		$this->assertInternalType(
			'array',
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

		if ( $dataValue->getErrors() === [] ){
			$this->assertInstanceOf( 'SMWDIProperty', $dataValue->getProperty() );
			$this->assertContains( $propertyName, $dataValue->getProperty()->getLabel() );
			if ( $dataValue->getDataItem()->getDIType() === SMWDataItem::TYPE_WIKIPAGE ){
				$this->assertEquals( $expectedValue, $dataValue->getWikiValue() );
			}
		} else {
			$this->assertInternalType( 'array', $dataValue->getErrors() );
		}

		// Check interface parameters
		$dataValue = DataValueFactory::getInstance()->newDataValueByProperty(
			$propertyDI,
			$value,
			'FooCaption',
			new DIWikiPage( 'Foo', NS_MAIN )
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

		if ( $dataValue->getErrors() === [] ){
			$this->assertInstanceOf( 'SMWDIProperty', $dataValue->getProperty() );
			$this->assertContains( $propertyName, $dataValue->getProperty()->getLabel() );
			if ( $dataValue->getDataItem()->getDIType() === SMWDataItem::TYPE_WIKIPAGE ){
				$this->assertEquals( $expectedValue, $dataValue->getWikiValue() );
			}
		} else {
			$this->assertInternalType( 'array', $dataValue->getErrors() );
		}

		// Check interface parameters
		$dataValue = DataValueFactory::getInstance()->newDataValueByText(
			$propertyName,
			$value,
			'FooCaption',
			new DIWikiPage( 'Foo', NS_MAIN )
		);

		$this->assertInstanceOf(
			$expectedInstance,
			$dataValue
		);
	}

	public function testTryToCreateDataValueUsingRestrictedPropertyValue() {

		$dataValue = DataValueFactory::getInstance()->newDataValueByText( 'Has subobject', 'Foo' );

		$this->assertInstanceOf(
			'\SMWErrorValue',
			$dataValue
		);

		$this->assertNotEmpty(
			$dataValue->getErrors()
		);
	}

	public function testToCreateDataValueUsingLegacyNewPropertyValueMethod() {

		$dataValue = DataValueFactory::getInstance()->newPropertyValue( 'Bar', 'Foo' );

		$this->assertInstanceOf(
			'\SMWDataValue',
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
			new DIWikiPage( 'Foo', SMW_NS_PROPERTY )
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
			new DIWikiPage( 'Foobar', SMW_NS_PROPERTY )
		);

		$this->assertInstanceOf(
			'\SMWPropertyValue',
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
			'SMWDataValue',
			$dataValue
		);
	}

	public function newDataValueByItemDataProvider() {

		$provider = [];

		$dataItem = new DIWikiPage( 'Foo', NS_MAIN );
		$property = new DIProperty( 'Bar' );

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
			[ 'URL'      , '_uri' ], // #0
			[ 'Page'     , '_wpg' ], // #1
			[ 'String'   , '_txt' ], // #2
			[ 'Text'     , '_txt' ], // #3
			[ 'Number'   , '_num' ], // #4
			[ 'Quantity' , '_qty' ], // #5
			[ 'Date'     , '_dat' ], // #6
			[ 'Email'    , '_ema' ], // #7
			[ ''         , ''     ], // #8
		];
	}

	public function dataItemIdDataProvider() {
		return [
			[ '_txt' , SMWDataItem::TYPE_BLOB ], // #0
			[ '_wpg' , SMWDataItem::TYPE_WIKIPAGE ], // #1
			[ '_num' , SMWDataItem::TYPE_NUMBER ], // #2
			[ '_dat' , SMWDataItem::TYPE_TIME ], // #3
			[ '_uri' , SMWDataItem::TYPE_URI ], // #4
			[ '_foo' , SMWDataItem::TYPE_NOTYPE ], // #5
		];
	}

	public function typeIdValueDataProvider() {
		return [
			[ '_txt'  , 'Bar'          , 'Bar'          , 'SMWStringValue' ], // #0
			[ '_txt'  , 'Bar[[ Foo ]]' , 'Bar[[ Foo ]]' , 'SMWStringValue' ], // #1
			[ '_txt'  , '9001'         , '9001'         , 'SMWStringValue' ], // #2
			[ '_txt'  , 1001           , '1001'         , 'SMWStringValue' ], // #3
			[ '_txt'  , '-%&$*'        , '-%&$*'        , 'SMWStringValue' ], // #4
			[ '_txt'  , '_Bar'         , '_Bar'         , 'SMWStringValue' ], // #5
			[ '_txt'  , 'bar'          , 'bar'          , 'SMWStringValue' ], // #6
			[ '-_txt' , 'Bar'          , 'Bar'          , 'SMWErrorValue' ], // #7

			[ '_wpg'  , 'Bar'          , 'Bar'          , 'SMWWikiPageValue' ], // #8
			[ '_wpg'  , 'Bar'          , 'Bar'          , 'SMWWikiPageValue' ], // #9
			[ '_wpg'  , 'Bar[[ Foo ]]' , 'Bar[[ Foo ]]' , 'SMWWikiPageValue' ], // #10
			[ '_wpg'  , '9001'         , '9001'         , 'SMWWikiPageValue' ], // #11
			[ '_wpg'  , 1001           , '1001'         , 'SMWWikiPageValue' ], // #12
			[ '_wpg'  , '-%&$*'        , '-%&$*'        , 'SMWWikiPageValue' ], // #13
			[ '_wpg'  , '_Bar'         , 'Bar'          , 'SMWWikiPageValue' ], // #14
			[ '_wpg'  , 'bar'          , 'Bar'          , 'SMWWikiPageValue' ], // #15
			[ '-_wpg' , 'Bar'          , 'Bar'          , 'SMWErrorValue' ], // #16

			[ '_dat' , '1 Jan 1970'    , '1 Jan 1970'   , 'SMWTimeValue' ], // #0
			[ '_uri' , 'Foo'           , 'Foo'          , 'SMWURIValue' ], // #0
			[ '_num' , 9001            , '9001'        , 'SMWNumberValue' ], // #0
			[ '_num' , 9001.5            , '9001.5'        , 'SMWNumberValue' ], // #0
		];
	}

	public function propertyValueDataProvider() {
		return [
			[ 'Foo'  , 'Bar'          , 'Bar'          , 'SMWDataValue' ], // #0
			[ 'Foo'  , 'Bar[[ Foo ]]' , 'Bar[[ Foo ]]' , 'SMWDataValue' ], // #1
			[ 'Foo'  , '9001'         , '9001'         , 'SMWDataValue' ], // #2
			[ 'Foo'  , 1001           , '1001'         , 'SMWDataValue' ], // #3
			[ 'Foo'  , '-%&$*'        , '-%&$*'        , 'SMWDataValue' ], // #4
			[ 'Foo'  , '_Bar'         , 'Bar'          , 'SMWDataValue' ], // #5
			[ 'Foo'  , 'bar'          , 'Bar'          , 'SMWDataValue' ], // #6
			[ '-Foo' , 'Bar'          , ''             , 'SMWErrorValue' ], // #7
			[ '_Foo' , 'Bar'          , ''             , 'SMWPropertyValue' ], // #8
		];
	}

	/**
	 * @return array
	 */
	public function propertyObjectValueDataProvider() {
		return [
			[ 'Foo'  , 'Bar'          , 'Bar'          , 'SMWDataValue' ], // #0
			[ 'Foo'  , 'Bar[[ Foo ]]' , 'Bar[[ Foo ]]' , 'SMWDataValue' ], // #1
			[ 'Foo'  , '9001'         , '9001'         , 'SMWDataValue' ], // #2
			[ 'Foo'  , 1001           , '1001'         , 'SMWDataValue' ], // #3
			[ 'Foo'  , '-%&$*'        , '-%&$*'        , 'SMWDataValue' ], // #4
			[ 'Foo'  , '_Bar'         , 'Bar'          , 'SMWDataValue' ], // #5
			[ 'Foo'  , 'bar'          , 'Bar'          , 'SMWDataValue' ], // #6
			[ '-Foo' , 'Bar'          , 'Bar'          , 'SMWWikiPageValue' ], // #7

			// Will fail with "must be an instance of SMWDIProperty, instance of SMWDIError give"
			// as propertyDI isn't checked therefore addPropertyValue() should be
			// used as it will return a proper object
			// array( '_Foo' , 'Bar'          , ''             , 'SMWDIProperty' ), // #8
		];
	}

}
