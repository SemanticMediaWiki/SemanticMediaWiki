<?php

namespace SMW\Tests;

use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMWDataItem;
use SMWPropertyValue;

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

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValueFactory',
			DataValueFactory::getInstance()
		);
	}

	/**
	 * @dataProvider dataItemIdDataProvider
	 */
	public function testGetDataItemId( $typeId, $expectedId ) {

		$this->assertEquals(
			$expectedId,
			DataValueFactory::getDataItemId( $typeId )
		);
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

		if ( $dataValue->getErrors() === array() ){
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

		$propertyDV = SMWPropertyValue::makeUserProperty( $propertyName );
		$propertyDI = $propertyDV->getDataItem();

		$dataValue = DataValueFactory::getInstance()->newDataValueByProperty( $propertyDI, $value );

		// Check the returned instance
		$this->assertInstanceOf( $expectedInstance, $dataValue );

		if ( $dataValue->getErrors() === array() ){
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

		if ( $dataValue->getErrors() === array() ){
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
	 * @dataProvider findTypeIdDataProvider
	 */
	public function testFindTypeID( $typeId, $expectedId ) {

		$this->assertEquals(
			$expectedId,
			DataValueFactory::findTypeID( $typeId )
		);
	}

	/**
	 * @dataProvider findTypeIdDataProvider
	 */
	public function testFindTypeLabel( $textId, $id ) {

		$textId = $textId === 'String' ? 'Text' : $textId;

		$this->assertEquals(
			$textId,
			DataValueFactory::findTypeLabel( $id ),
			'Asserts that findTypeLabel() returns a user label'
		);

	}

	public function testGetKnownTypeLabels() {

		$this->assertInternalType(
			'array',
			DataValueFactory::getKnownTypeLabels()
		);
	}


	public function testRegisterDatatype() {

		DataValueFactory::registerDatatype( '_foo', '\SMW\FooValue', SMWDataItem::TYPE_NOTYPE, 'FooValue' );

		$this->assertEquals(
			'FooValue',
			DataValueFactory::findTypeLabel( '_foo' )
		);

		DataValueFactory::getInstance()->registerDatatype( '_foo', '\SMW\FooValue', SMWDataItem::TYPE_NOTYPE, 'FooValue' );

		$this->assertEquals(
			'FooValue',
			DataValueFactory::getInstance()->findTypeLabel( '_foo' )
		);
	}

	public function testRegisterDatatypeAlias() {

		DataValueFactory::registerDatatypeAlias( '_foo', 'Bar' );

		$this->assertEquals(
			'_foo',
			DataValueFactory::findTypeID( 'Bar' )
		);

		DataValueFactory::getInstance()->registerDatatypeAlias( '_foo', 'Bar' );

		$this->assertEquals(
			'_foo',
			DataValueFactory::getInstance()->findTypeID( 'Bar' )
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

		$dataValue = DataValueFactory::getInstance()->newPropertyValueByLabel( 'Foo' );

		$this->assertInstanceOf(
			'\SMWPropertyValue',
			$dataValue
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

		$provider = array();

		$dataItem = new DIWikiPage( 'Foo', NS_MAIN );
		$property = new DIProperty( 'Bar' );

		// #0
		$provider[] = array(
			array(
				'dataItem' => $dataItem,
				'property' => null,
				'caption'  => false
			)
		);

		// #0
		$provider[] = array(
			array(
				'dataItem' => $dataItem,
				'property' => $property,
				'caption'  => false
			)
		);

		// #1
		$provider[] = array(
			array(
				'dataItem' => $dataItem,
				'property' => null,
				'caption'  => 'Foo'
			)
		);

		// #2
		$provider[] = array(
			array(
				'dataItem' => $dataItem,
				'property' => $property,
				'caption'  => 'Bar'
			)
		);

		return $provider;
	}

	public function findTypeIdDataProvider() {
		return array(
			array( 'URL'      , '_uri' ), // #0
			array( 'Page'     , '_wpg' ), // #1
			array( 'String'   , '_txt' ), // #2
			array( 'Text'     , '_txt' ), // #3
			array( 'Number'   , '_num' ), // #4
			array( 'Quantity' , '_qty' ), // #5
			array( 'Date'     , '_dat' ), // #6
			array( 'Email'    , '_ema' ), // #7
			array( ''         , ''     ), // #8
		);
	}

	public function dataItemIdDataProvider() {
		return array(
			array( '_txt' , SMWDataItem::TYPE_BLOB ), // #0
			array( '_wpg' , SMWDataItem::TYPE_WIKIPAGE ), // #1
			array( '_num' , SMWDataItem::TYPE_NUMBER ), // #2
			array( '_dat' , SMWDataItem::TYPE_TIME ), // #3
			array( '_uri' , SMWDataItem::TYPE_URI ), // #4
			array( '_foo' , SMWDataItem::TYPE_NOTYPE ), // #5
		);
	}

	public function typeIdValueDataProvider() {
		return array(
			array( '_txt'  , 'Bar'          , 'Bar'          , 'SMWStringValue' ), // #0
			array( '_txt'  , 'Bar[[ Foo ]]' , 'Bar[[ Foo ]]' , 'SMWStringValue' ), // #1
			array( '_txt'  , '9001'         , '9001'         , 'SMWStringValue' ), // #2
			array( '_txt'  , 1001           , '1001'         , 'SMWStringValue' ), // #3
			array( '_txt'  , '-%&$*'        , '-%&$*'        , 'SMWStringValue' ), // #4
			array( '_txt'  , '_Bar'         , '_Bar'         , 'SMWStringValue' ), // #5
			array( '_txt'  , 'bar'          , 'bar'          , 'SMWStringValue' ), // #6
			array( '-_txt' , 'Bar'          , 'Bar'          , 'SMWErrorValue' ), // #7

			array( '_wpg'  , 'Bar'          , 'Bar'          , 'SMWWikiPageValue' ), // #8
			array( '_wpg'  , 'Bar'          , 'Bar'          , 'SMWWikiPageValue' ), // #9
			array( '_wpg'  , 'Bar[[ Foo ]]' , 'Bar[[ Foo ]]' , 'SMWWikiPageValue' ), // #10
			array( '_wpg'  , '9001'         , '9001'         , 'SMWWikiPageValue' ), // #11
			array( '_wpg'  , 1001           , '1001'         , 'SMWWikiPageValue' ), // #12
			array( '_wpg'  , '-%&$*'        , '-%&$*'        , 'SMWWikiPageValue' ), // #13
			array( '_wpg'  , '_Bar'         , 'Bar'          , 'SMWWikiPageValue' ), // #14
			array( '_wpg'  , 'bar'          , 'Bar'          , 'SMWWikiPageValue' ), // #15
			array( '-_wpg' , 'Bar'          , 'Bar'          , 'SMWErrorValue' ), // #16

			array( '_dat' , '1 Jan 1970'    , '1 Jan 1970'   , 'SMWTimeValue' ), // #0
			array( '_uri' , 'Foo'           , 'Foo'          , 'SMWURIValue' ), // #0
			array( '_num' , 9001            , '9001'        , 'SMWNumberValue' ), // #0
			array( '_num' , 9001.5            , '9001.5'        , 'SMWNumberValue' ), // #0
		);
	}

	public function propertyValueDataProvider() {
		return array(
			array( 'Foo'  , 'Bar'          , 'Bar'          , 'SMWDataValue' ), // #0
			array( 'Foo'  , 'Bar[[ Foo ]]' , 'Bar[[ Foo ]]' , 'SMWDataValue' ), // #1
			array( 'Foo'  , '9001'         , '9001'         , 'SMWDataValue' ), // #2
			array( 'Foo'  , 1001           , '1001'         , 'SMWDataValue' ), // #3
			array( 'Foo'  , '-%&$*'        , '-%&$*'        , 'SMWDataValue' ), // #4
			array( 'Foo'  , '_Bar'         , 'Bar'          , 'SMWDataValue' ), // #5
			array( 'Foo'  , 'bar'          , 'Bar'          , 'SMWDataValue' ), // #6
			array( '-Foo' , 'Bar'          , ''             , 'SMWErrorValue' ), // #7
			array( '_Foo' , 'Bar'          , ''             , 'SMWPropertyValue' ), // #8
		);
	}

	/**
	 * @return array
	 */
	public function propertyObjectValueDataProvider() {
		return array(
			array( 'Foo'  , 'Bar'          , 'Bar'          , 'SMWDataValue' ), // #0
			array( 'Foo'  , 'Bar[[ Foo ]]' , 'Bar[[ Foo ]]' , 'SMWDataValue' ), // #1
			array( 'Foo'  , '9001'         , '9001'         , 'SMWDataValue' ), // #2
			array( 'Foo'  , 1001           , '1001'         , 'SMWDataValue' ), // #3
			array( 'Foo'  , '-%&$*'        , '-%&$*'        , 'SMWDataValue' ), // #4
			array( 'Foo'  , '_Bar'         , 'Bar'          , 'SMWDataValue' ), // #5
			array( 'Foo'  , 'bar'          , 'Bar'          , 'SMWDataValue' ), // #6
			array( '-Foo' , 'Bar'          , 'Bar'          , 'SMWWikiPageValue' ), // #7

			// Will fail with "must be an instance of SMWDIProperty, instance of SMWDIError give"
			// as propertyDI isn't checked therefore addPropertyValue() should be
			// used as it will return a proper object
			// array( '_Foo' , 'Bar'          , ''             , 'SMWDIProperty' ), // #8
		);
	}

}
