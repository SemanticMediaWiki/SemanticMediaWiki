<?php

namespace SMW\Tests\Query\Parser;

use SMW\Query\DescriptionFactory;
use SMW\DIProperty;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;

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

}
