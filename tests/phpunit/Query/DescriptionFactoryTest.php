<?php

namespace SMW\Tests\Query;

use PHPUnit\Framework\TestCase;
use SMW\DataItemFactory;
use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\ContainerSemanticData;
use SMW\DataValues\DataValue;
use SMW\DataValues\MonolingualTextValue;
use SMW\DataValues\ValueFormatters\MonolingualTextValueFormatter;
use SMW\DataValues\ValueParsers\MonolingualTextValueParser;
use SMW\Query\DescriptionBuilderRegistry;
use SMW\Query\DescriptionFactory;
use SMW\Query\Language\ClassDescription;
use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Description;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\NamespaceDescription;
use SMW\Query\Language\SomeProperty;
use SMW\Query\Language\ThingDescription;
use SMW\Query\Language\ValueDescription;
use SMW\Services\DataValueServiceFactory;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers SMW\Query\DescriptionFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class DescriptionFactoryTest extends TestCase {

	private $testEnvironment;
	private $dataItemFactory;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DescriptionFactory::class,
			new DescriptionFactory()
		);
	}

	public function testCanConstructValueDescription() {
		$dataItem = $this->getMockBuilder( DataItem::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			ValueDescription::class,
			$instance->newValueDescription( $dataItem )
		);
	}

	public function testCanConstructSomeProperty() {
		$property = $this->getMockBuilder( Property::class )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			SomeProperty::class,
			$instance->newSomeProperty( $property, $description )
		);
	}

	public function testCanConstructThingDescription() {
		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			ThingDescription::class,
			$instance->newThingDescription()
		);
	}

	public function testCanConstructDisjunction() {
		$descriptions = [];

		$description = $this->getMockBuilder( SomeProperty::class )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->once() )
			->method( 'getPrintRequests' )
			->willReturn( [] );

		$descriptions[] = $description;

		$description = $this->getMockBuilder( ValueDescription::class )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->once() )
			->method( 'getPrintRequests' )
			->willReturn( [] );

		$descriptions[] = $description;

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			Disjunction::class,
			$instance->newDisjunction( $descriptions )
		);
	}

	public function testCanConstructConjunction() {
		$descriptions = [];

		$description = $this->getMockBuilder( SomeProperty::class )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->once() )
			->method( 'getPrintRequests' )
			->willReturn( [] );

		$descriptions[] = $description;

		$description = $this->getMockBuilder( ValueDescription::class )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->once() )
			->method( 'getPrintRequests' )
			->willReturn( [] );

		$descriptions[] = $description;

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			Conjunction::class,
			$instance->newConjunction( $descriptions )
		);
	}

	public function testCanConstructNamespaceDescription() {
		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			NamespaceDescription::class,
			$instance->newNamespaceDescription( SMW_NS_PROPERTY )
		);
	}

	public function testCanConstructClassDescription() {
		$category = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			ClassDescription::class,
			$instance->newClassDescription( $category )
		);
	}

	public function testCanConstructClassDescription_Categories() {
		$category_1 = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$category_2 = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			ClassDescription::class,
			$instance->newClassDescription( [ $category_1, $category_2 ] )
		);
	}

	public function testCanConstructConceptDescription() {
		$concept = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			ConceptDescription::class,
			$instance->newConceptDescription( $concept )
		);
	}

	public function testCanConstructDescriptionFromInvalidDataValue() {
		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->setMethods( [ 'isValid' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'isValid' )
			->willReturn( false );

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			ThingDescription::class,
			$instance->newFromDataValue( $dataValue )
		);
	}

	public function testCanConstructDescriptionFromValidDataValue() {
		$dataValue = $this->getMockBuilder( DataValue::class )
			->disableOriginalConstructor()
			->setMethods( [ 'isValid', 'getProperty', 'getDataItem', 'getWikiValue' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'isValid' )
			->willReturn( true );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->willReturn( $this->dataItemFactory->newDIProperty( 'Foo' ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->willReturn( $this->dataItemFactory->newDIBlob( 'Bar' ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getWikiValue' )
			->willReturn( 'Bar' );

		$dataValueServiceFactory = $this->getMockBuilder( DataValueServiceFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$dataValueServiceFactory->expects( $this->atLeastOnce() )
			->method( 'getDescriptionBuilderRegistry' )
			->willReturn( new DescriptionBuilderRegistry() );

		$dataValue->setDataValueServiceFactory(
			$dataValueServiceFactory
		);

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			SomeProperty::class,
			$instance->newFromDataValue( $dataValue )
		);
	}

	public function testCanConstructDescriptionFromMonolingualTextValue() {
		$containerSemanticData = $this->getMockBuilder( ContainerSemanticData::class )
			->disableOriginalConstructor()
			->getMock();

		$containerSemanticData->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->willReturn( [ $this->dataItemFactory->newDIBlob( 'Bar' ) ] );

		$dataValue = $this->getMockBuilder( MonolingualTextValue::class )
			->disableOriginalConstructor()
			->setMethods( [ 'isValid', 'getProperty', 'getDataItem' ] )
			->getMock();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'isValid' )
			->willReturn( true );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->willReturn( $this->dataItemFactory->newDIContainer( $containerSemanticData ) );

		$monolingualTextValueFormatter = new MonolingualTextValueFormatter();
		$monolingualTextValueFormatter->setDataValue( $dataValue );

		$monolingualTextValueParser = $this->getMockBuilder( MonolingualTextValueParser::class )
			->disableOriginalConstructor()
			->getMock();

		$dataValueServiceFactory = $this->getMockBuilder( DataValueServiceFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$dataValueServiceFactory->expects( $this->atLeastOnce() )
			->method( 'getValueFormatter' )
			->willReturn( $monolingualTextValueFormatter );

		$dataValueServiceFactory->expects( $this->atLeastOnce() )
			->method( 'getValueParser' )
			->willReturn( $monolingualTextValueParser );

		$dataValueServiceFactory->expects( $this->atLeastOnce() )
			->method( 'getDescriptionBuilderRegistry' )
			->willReturn( new DescriptionBuilderRegistry() );

		$dataValue->setDataValueServiceFactory(
			$dataValueServiceFactory
		);

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			Conjunction::class,
			$instance->newFromDataValue( $dataValue )
		);
	}

	public function testCanConstructDescriptionFromMonolingualTextValueWithProperty() {
		$containerSemanticData = $this->getMockBuilder( ContainerSemanticData::class )
			->disableOriginalConstructor()
			->getMock();

		$containerSemanticData->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->willReturn( [ $this->dataItemFactory->newDIBlob( 'Bar' ) ] );

		$dataValue = $this->getMockBuilder( MonolingualTextValue::class )
			->disableOriginalConstructor()
			->setMethods( [ 'isValid', 'getProperty', 'getDataItem' ] )
			->getMock();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'isValid' )
			->willReturn( true );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->willReturn( $this->dataItemFactory->newDIProperty( 'Foo' ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->willReturn( $this->dataItemFactory->newDIContainer( $containerSemanticData ) );

		$monolingualTextValueFormatter = new MonolingualTextValueFormatter();
		$monolingualTextValueFormatter->setDataValue( $dataValue );

		$monolingualTextValueParser = $this->getMockBuilder( MonolingualTextValueParser::class )
			->disableOriginalConstructor()
			->getMock();

		$dataValueServiceFactory = $this->getMockBuilder( DataValueServiceFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$dataValueServiceFactory->expects( $this->atLeastOnce() )
			->method( 'getValueFormatter' )
			->willReturn( $monolingualTextValueFormatter );

		$dataValueServiceFactory->expects( $this->atLeastOnce() )
			->method( 'getValueParser' )
			->willReturn( $monolingualTextValueParser );

		$dataValueServiceFactory->expects( $this->atLeastOnce() )
			->method( 'getDescriptionBuilderRegistry' )
			->willReturn( new DescriptionBuilderRegistry() );

		$dataValue->setDataValueServiceFactory(
			$dataValueServiceFactory
		);

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			SomeProperty::class,
			$instance->newFromDataValue( $dataValue )
		);
	}

}
