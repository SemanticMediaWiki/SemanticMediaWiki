<?php

namespace SMW\Tests\SQLStore;

use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\SQLStore\PropertyTableRowDiffer;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SQLStore\PropertyTableRowDiffer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class PropertyTableRowDifferTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\SQLStore\PropertyTableRowDiffer',
			new PropertyTableRowDiffer( $store )
		);
	}

	public function testComputeTableRowDiffForEmptyPropertyTables() {

		$semanticData = new SemanticData(
			new DIWikiPage( 'Foo', NS_MAIN )
		);

		$propertyTables = array();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->setMethods( array( 'getPropertyTables' ) )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( $propertyTables ) );

		$instance = new PropertyTableRowDiffer( $store );

		$result = $instance->computeTableRowDiffFor(
			42,
			$semanticData
		);

		$this->assertInternalType(
			'array',
			$result
		);
	}

	public function testCompositePropertyTableDiffIterator() {

		$semanticData = new SemanticData(
			new DIWikiPage( 'Foo', NS_MAIN )
		);

		$propertyTables = array();

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->setMethods( array( 'getPropertyTables' ) )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( $propertyTables ) );

		$instance = new PropertyTableRowDiffer( $store );
		$instance->resetCompositePropertyTableDiff();

		$result = $instance->computeTableRowDiffFor(
			42,
			$semanticData
		);

		$this->assertInstanceOf(
			'\SMW\SQLStore\CompositePropertyTableDiffIterator',
			$instance->getCompositePropertyTableDiff()
		);
	}

	public function testCompositePropertyTableDiffIteratorWithUnknownFixedProperty() {

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->once() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( true ) );

		$propertyTable->expects( $this->once() )
			->method( 'isFixedPropertyTable' )
			->will( $this->returnValue( true ) );

		$propertyTable->expects( $this->once() )
			->method( 'getFixedProperty' )
			->will( $this->returnValue( '_UNKNOWN_FIXED_PROPERTY' ) );

		$semanticData = new SemanticData(
			new DIWikiPage( 'Foo', NS_MAIN )
		);

		$propertyTables = array( $propertyTable );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->setMethods( array( 'getPropertyTables' ) )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( $propertyTables ) );

		$instance = new PropertyTableRowDiffer( $store );
		$instance->resetCompositePropertyTableDiff();

		$result = $instance->computeTableRowDiffFor(
			42,
			$semanticData
		);

		$this->assertInstanceOf(
			'\SMW\SQLStore\CompositePropertyTableDiffIterator',
			$instance->getCompositePropertyTableDiff()
		);

		$this->assertEmpty(
			$instance->getCompositePropertyTableDiff()->getFixedPropertyRecords()
		);
	}

}
