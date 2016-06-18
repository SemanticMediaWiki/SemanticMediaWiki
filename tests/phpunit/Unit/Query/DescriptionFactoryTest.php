<?php

namespace SMW\Tests\Query\Parser;

use SMW\DataItemFactory;
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

		$descriptions = array();

		$description = $this->getMockBuilder( '\SMW\Query\Language\SomeProperty' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->once() )
			->method( 'getPrintRequests' )
			->will( $this->returnValue( array() ) );

		$descriptions[] = $description;

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

		$description->expects( $this->once() )
			->method( 'getPrintRequests' )
			->will( $this->returnValue( array() ) );

		$descriptions[] = $description;

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			'SMW\Query\Language\Disjunction',
			$instance->newDisjunction( $descriptions )
		);
	}

	public function testCanConstructConjunction() {

		$descriptions = array();

		$description = $this->getMockBuilder( '\SMW\Query\Language\SomeProperty' )
			->disableOriginalConstructor()
			->getMock();

		$descriptions[] = $description;

		$description = $this->getMockBuilder( '\SMW\Query\Language\ValueDescription' )
			->disableOriginalConstructor()
			->getMock();

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
			->setMethods( array( 'isValid' ) )
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
			->setMethods( array( 'isValid', 'getProperty', 'getDataItem' ) )
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
			->will( $this->returnValue( array( $this->dataItemFactory->newDIBlob( 'Bar' ) ) ) );

		$dataValue = $this->getMockBuilder( '\SMW\DataValues\MonolingualTextValue' )
			->disableOriginalConstructor()
			->setMethods( array( 'isValid', 'getProperty', 'getDataItem' ) )
			->getMock();

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'isValid' )
			->will( $this->returnValue( true ) );

		$dataValue->expects( $this->atLeastOnce() )
			->method( 'getDataItem' )
			->will( $this->returnValue( $this->dataItemFactory->newDIContainer( $containerSemanticData ) ) );

		$instance = new DescriptionFactory();

		$this->assertInstanceOf(
			'SMW\Query\Language\Conjunction',
			$instance->newFromDataValue( $dataValue )
		);
	}

}
