<?php

namespace SMW\Tests\Query;

use SMW\DataItemFactory;
use SMW\DataValues\ValueFormatters\MonolingualTextValueFormatter;
use SMW\Query\DescriptionFactory;
use SMW\Tests\TestEnvironment;

/**
 * @covers SMW\Query\DescriptionFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DescriptionFactoryTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $dataItemFactory;

	protected function setUp() {
		$this->testEnvironment = new TestEnvironment();
		$this->dataItemFactory = new DataItemFactory();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'SMW\Query\DescriptionFactory',
			new DescriptionFactory()
		);
	}

	public function testCanConstructValueDescription() {

		$dataItem = $this->getMockBuilder( '\SMWDataItem' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			'SMW\Query\Language\ValueDescription',
			$instance->newValueDescription( $dataItem )
		);
	}

	public function testCanConstructSomeProperty() {

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			'SMW\Query\Language\SomeProperty',
			$instance->newSomeProperty( $property, $description )
		);
	}

	public function testCanConstructThingDescription() {

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			'SMW\Query\Language\ThingDescription',
			$instance->newThingDescription()
		);
	}

	public function testCanConstructDisjunction() {

		$descriptions = [];

		$description = $this->getMockBuilder( '\SMW\Query\Language\SomeProperty' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->once() )
			->method( 'getPrintRequests' )
			->will( $this->returnValue( [] ) );

		$descriptions[] = $description;

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->once() )
			->method( 'getPrintRequests' )
			->will( $this->returnValue( [] ) );

		$descriptions[] = $description;

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			'SMW\Query\Language\Disjunction',
			$instance->newDisjunction( $descriptions )
		);
	}

	public function testCanConstructConjunction() {

		$descriptions = [];

		$description = $this->getMockBuilder( '\SMW\Query\Language\SomeProperty' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->once() )
			->method( 'getPrintRequests' )
			->will( $this->returnValue( [] ) );

		$descriptions[] = $description;

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->once() )
			->method( 'getPrintRequests' )
			->will( $this->returnValue( [] ) );

		$descriptions[] = $description;

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			'SMW\Query\Language\Conjunction',
			$instance->newConjunction( $descriptions )
		);
	}

	public function testCanConstructNamespaceDescription() {

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			'SMW\Query\Language\NamespaceDescription',
			$instance->newNamespaceDescription( SMW_NS_PROPERTY )
		);
	}

	public function testCanConstructClassDescription() {

		$category = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			'SMW\Query\Language\ClassDescription',
			$instance->newClassDescription( $category )
		);
	}

	public function testCanConstructClassDescription_Categories() {

		$category_1 = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$category_2 = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			'SMW\Query\Language\ClassDescription',
			$instance->newClassDescription( [ $category_1, $category_2 ] )
		);
	}

	public function testCanConstructConceptDescription() {

		$concept = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			'SMW\Query\Language\ConceptDescription',
			$instance->newConceptDescription( $concept )
		);
	}

	public function testCanConstructDescriptionFromInvalidDataValue() {

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'isValid' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'isValid' )
			->will( $this->returnValue( false ) );

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			'SMW\Query\Language\ThingDescription',
			$instance->newFromDataValue( $dataValue )
		);
	}

	public function testCanConstructDescriptionFromValidDataValue() {

		$dataValue = $this->getMockBuilder( '\SMWDataValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'isValid', 'getProperty', 'getDataItem', 'getWikiValue' ] )
			->getMockForAbstractClass();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'isValid' )
			->will( $this->returnValue( true ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->will( $this->returnValue( $this->dataItemFactory->newDIProperty( 'Foo' ) ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->dataItemFactory->newDIBlob( 'Bar' ) ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getWikiValue' )
			->will( $this->returnValue( 'Bar' ) );

		$dataValueServiceFactory = $this->getMockBuilder( '\SMW\Services\DataValueServiceFactory' )
			->disableOriginalConstructor()
			->getMock();

		$dataValueServiceFactory->expects( $this->atLeastOnce() )
			->method( 'getDescriptionBuilderRegistry' )
			->will( $this->returnValue( new \SMW\Query\DescriptionBuilderRegistry() ) );

		$dataValue->setDataValueServiceFactory(
			$dataValueServiceFactory
		);

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			'SMW\Query\Language\SomeProperty',
			$instance->newFromDataValue( $dataValue )
		);
	}

	public function testCanConstructDescriptionFromMonolingualTextValue() {

		$containerSemanticData = $this->getMockBuilder( '\SMWContainerSemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$containerSemanticData->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIBlob( 'Bar' ) ] ) );

		$dataValue = $this->getMockBuilder( '\SMW\DataValues\MonolingualTextValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'isValid', 'getProperty', 'getDataItem' ] )
			->getMock();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'isValid' )
			->will( $this->returnValue( true ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->dataItemFactory->newDIContainer( $containerSemanticData ) ) );

		$monolingualTextValueFormatter = new MonolingualTextValueFormatter();
		$monolingualTextValueFormatter->setDataValue( $dataValue );

		$monolingualTextValueParser = $this->getMockBuilder( '\SMW\DataValues\ValueParsers\MonolingualTextValueParser' )
			->disableOriginalConstructor()
			->getMock();

		$dataValueServiceFactory = $this->getMockBuilder( '\SMW\Services\DataValueServiceFactory' )
			->disableOriginalConstructor()
			->getMock();

		$dataValueServiceFactory->expects( $this->atLeastOnce() )
			->method( 'getValueFormatter' )
			->will( $this->returnValue( $monolingualTextValueFormatter ) );

		$dataValueServiceFactory->expects( $this->atLeastOnce() )
			->method( 'getValueParser' )
			->will( $this->returnValue( $monolingualTextValueParser ) );

		$dataValueServiceFactory->expects( $this->atLeastOnce() )
			->method( 'getDescriptionBuilderRegistry' )
			->will( $this->returnValue( new \SMW\Query\DescriptionBuilderRegistry() ) );

		$dataValue->setDataValueServiceFactory(
			$dataValueServiceFactory
		);

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			'SMW\Query\Language\Conjunction',
			$instance->newFromDataValue( $dataValue )
		);
	}

	public function testCanConstructDescriptionFromMonolingualTextValueWithProperty() {

		$containerSemanticData = $this->getMockBuilder( '\SMWContainerSemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$containerSemanticData->expects( $this->atLeastOnce() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( [ $this->dataItemFactory->newDIBlob( 'Bar' ) ] ) );

		$dataValue = $this->getMockBuilder( '\SMW\DataValues\MonolingualTextValue' )
			->disableOriginalConstructor()
			->setMethods( [ 'isValid', 'getProperty', 'getDataItem' ] )
			->getMock();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'isValid' )
			->will( $this->returnValue( true ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getProperty' )
			->will( $this->returnValue( $this->dataItemFactory->newDIProperty( 'Foo' ) ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->dataItemFactory->newDIContainer( $containerSemanticData ) ) );

		$monolingualTextValueFormatter = new MonolingualTextValueFormatter();
		$monolingualTextValueFormatter->setDataValue( $dataValue );

		$monolingualTextValueParser = $this->getMockBuilder( '\SMW\DataValues\ValueParsers\MonolingualTextValueParser' )
			->disableOriginalConstructor()
			->getMock();

		$dataValueServiceFactory = $this->getMockBuilder( '\SMW\Services\DataValueServiceFactory' )
			->disableOriginalConstructor()
			->getMock();

		$dataValueServiceFactory->expects( $this->atLeastOnce() )
			->method( 'getValueFormatter' )
			->will( $this->returnValue( $monolingualTextValueFormatter ) );

		$dataValueServiceFactory->expects( $this->atLeastOnce() )
			->method( 'getValueParser' )
			->will( $this->returnValue( $monolingualTextValueParser ) );

		$dataValueServiceFactory->expects( $this->atLeastOnce() )
			->method( 'getDescriptionBuilderRegistry' )
			->will( $this->returnValue( new \SMW\Query\DescriptionBuilderRegistry() ) );

		$dataValue->setDataValueServiceFactory(
			$dataValueServiceFactory
		);

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			'SMW\Query\Language\SomeProperty',
			$instance->newFromDataValue( $dataValue )
		);
	}

}
