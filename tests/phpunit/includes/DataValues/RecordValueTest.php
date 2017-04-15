<?php

namespace SMW\Tests\DataValues;

use SMWRecordValue as RecordValue;
use SMW\DataItemFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMWRecordValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class RecordValueTest extends \PHPUnit_Framework_TestCase {

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
			'\SMWRecordValue',
			new RecordValue()
		);
	}

	public function testGetPropertyDataItems() {

		$expected = array(
			$this->dataItemFactory->newDIProperty( 'Bar' ),
			$this->dataItemFactory->newDIProperty( 'Foobar' )
		);

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'getRedirectTarget' ) )
			->getMockForAbstractClass();

		$this->propertySpecificationLookup->expects( $this->atLeastOnce() )
			->method( 'getFieldListBy' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( 'Bar;Foobar' ) ) );

		$store->expects( $this->any() )
			->method( 'getRedirectTarget' )
			->will( $this->returnArgument( 0 ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new RecordValue();
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
			->setMethods( array( 'getRedirectTarget' ) )
			->getMockForAbstractClass();

		$this->propertySpecificationLookup->expects( $this->atLeastOnce() )
			->method( 'getFieldListBy' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( 'Bar;Foobar' ) ) );

		$store->expects( $this->any() )
			->method( 'getRedirectTarget' )
			->will( $this->returnArgument( 0 ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new RecordValue();
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

		$instance = new RecordValue();
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
			->setMethods( array( 'getRedirectTarget' ) )
			->getMockForAbstractClass();

		$this->propertySpecificationLookup->expects( $this->atLeastOnce() )
			->method( 'getFieldListBy' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( 'Bar;Foobar' ) ) );

		$store->expects( $this->any() )
			->method( 'getRedirectTarget' )
			->will( $this->returnArgument( 0 ) );

		$this->testEnvironment->registerObject( 'Store', $store );

		$instance = new RecordValue();
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

		$instance = new RecordValue();

		$this->assertEquals(
			array( 'abc', '1;2', 3 ),
			$instance->getValuesFromString( 'abc;1\;2;3' )
		);
	}

	/**
	 * @dataProvider valueProvider
	 */
	public function testGetQueryDescription( $properties, $value, $expected ) {

		$instance = new RecordValue( '_rec' );
		$instance->setFieldProperties( $properties );

		$description = $instance->getQueryDescription( htmlspecialchars( $value ) );

		$this->assertEquals(
			$expected['description'],
			$description->getQueryString()
		);
	}

	/**
	 * @dataProvider valueProvider
	 */
	public function testGetWikiValue( $properties, $value, $expected ) {

		$instance = new RecordValue( '_rec' );
		$instance->setFieldProperties( $properties );

		$instance->setUserValue( $value );

		$this->assertEquals(
			$expected['wikivalue'],
			$instance->getWikiValue()
		);
	}

	public function valueProvider() {

		$dataItemFactory = new DataItemFactory();

		$properties = array(
			$dataItemFactory->newDIProperty( 'Foo' ),
			$dataItemFactory->newDIProperty( 'Bar' ),
			'InvalidFieldPropertyNotSet'
		);

		$provider[] = array(
			$properties,
			"Title without special characters;2001",
			array(
				'description' => "[[Foo::Title without special characters]] [[Bar::2001]]",
				'wikivalue'   => "Title without special characters; 2001"
			)

		);

		$provider[] = array(
			$properties,
			"Title with $&%'* special characters;(..&^%..)",
			array(
				'description' => "[[Foo::Title with $&%'* special characters]] [[Bar::(..&^%..)]]",
				'wikivalue'   => "Title with $&%'* special characters; (..&^%..)"
			)
		);

		$provider[] = array(
			$properties,
			" Title with space before ; After the divider ",
			array(
				'description' => "[[Foo::Title with space before]] [[Bar::After the divider]]",
				'wikivalue'   => "Title with space before; After the divider"
			)
		);

		$provider[] = array(
			$properties,
			" Title with backslash\; escape ; After the divider ",
			array(
				'description' => "[[Foo::Title with backslash; escape]] [[Bar::After the divider]]",
				'wikivalue'   => "Title with backslash\; escape; After the divider"
			)
		);

		return $provider;
	}

}
