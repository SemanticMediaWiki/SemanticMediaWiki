<?php

namespace SMW\Tests\DataValues;

use SMW\DataItemFactory;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\TestEnvironment;
use SMWRecordValue as RecordValue;

/**
 * @covers \SMWRecordValue
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class RecordValueTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $testEnvironment;
	private $dataItemFactory;

	private $propertySpecificationLookup;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$this->propertySpecificationLookup = $this->getMockBuilder( '\SMW\Property\SpecificationLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $this->propertySpecificationLookup );
	}

	protected function tearDown(): void {
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
			->willReturn( $this->dataItemFactory->newDIBlob( 'Bar;Foobar' ) );

		$store->expects( $this->any() )
			->method( 'getRedirectTarget' )
			->willReturnArgument( 0 );

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
			->setMethods( [ 'getRedirectTarget' ] )
			->getMockForAbstractClass();

		$this->propertySpecificationLookup->expects( $this->atLeastOnce() )
			->method( 'getFieldListBy' )
			->willReturn( $this->dataItemFactory->newDIBlob( 'Bar;Foobar' ) );

		$store->expects( $this->any() )
			->method( 'getRedirectTarget' )
			->willReturnArgument( 0 );

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
			->setMethods( [ 'getRedirectTarget' ] )
			->getMockForAbstractClass();

		$this->propertySpecificationLookup->expects( $this->atLeastOnce() )
			->method( 'getFieldListBy' )
			->willReturn( $this->dataItemFactory->newDIBlob( 'Bar;Foobar' ) );

		$store->expects( $this->any() )
			->method( 'getRedirectTarget' )
			->willReturnArgument( 0 );

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
			[ 'abc', '1;2', 3 ],
			$instance->getValuesFromString( 'abc;1\;2;3' )
		);
	}

	/**
	 * @dataProvider valueProvider
	 */
	public function testGetQueryDescription( $properties, $value, $expected ) {
		$dataValueServiceFactory = $this->getMockBuilder( '\SMW\Services\dataValueServiceFactory' )
			->disableOriginalConstructor()
			->getMock();

		$dataValueServiceFactory->expects( $this->atLeastOnce() )
			->method( 'getDescriptionBuilderRegistry' )
			->willReturn( new \SMW\Query\DescriptionBuilderRegistry() );

		$instance = new RecordValue( '_rec' );
		$instance->setFieldProperties( $properties );
		$instance->setDataValueServiceFactory( $dataValueServiceFactory );

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

		$properties = [
			$dataItemFactory->newDIProperty( 'Foo' ),
			$dataItemFactory->newDIProperty( 'Bar' ),
			'InvalidFieldPropertyNotSet'
		];

		$provider[] = [
			$properties,
			"Title without special characters;2001",
			[
				'description' => "[[Foo::Title without special characters]] [[Bar::2001]]",
				'wikivalue'   => "Title without special characters; 2001"
			]

		];

		$provider[] = [
			$properties,
			"Title with $&%'* special characters;(..&^%..)",
			[
				'description' => "[[Foo::Title with $&%'* special characters]] [[Bar::(..&^%..)]]",
				'wikivalue'   => "Title with $&%'* special characters; (..&^%..)"
			]
		];

		$provider[] = [
			$properties,
			" Title with space before ; After the divider ",
			[
				'description' => "[[Foo::Title with space before]] [[Bar::After the divider]]",
				'wikivalue'   => "Title with space before; After the divider"
			]
		];

		$provider[] = [
			$properties,
			" Title with backslash\; escape ; After the divider ",
			[
				'description' => "[[Foo::Title with backslash; escape]] [[Bar::After the divider]]",
				'wikivalue'   => "Title with backslash\; escape; After the divider"
			]
		];

		return $provider;
	}

}
