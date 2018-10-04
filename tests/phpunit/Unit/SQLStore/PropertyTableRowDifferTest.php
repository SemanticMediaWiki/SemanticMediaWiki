<?php

namespace SMW\Tests\SQLStore;

use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\SQLStore\PropertyTableRowDiffer;

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

	private $propertyTableRowMapper;

	protected function setUp() {

		$this->propertyTableRowMapper = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableRowMapper' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyTableRowMapper->expects( $this->any() )
			->method( 'mapToRows' )
			->will( $this->returnValue( [ [], [], [], [] ] ) );
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\SQLStore\PropertyTableRowDiffer',
			new PropertyTableRowDiffer( $store, $this->propertyTableRowMapper )
		);
	}

	public function testComputeTableRowDiffForEmptyPropertyTables() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );
		$semanticData = new SemanticData( $subject );

		$propertyTables = [];

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->setMethods( [ 'getPropertyTables' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( $propertyTables ) );

		$instance = new PropertyTableRowDiffer(
			$store,
			$this->propertyTableRowMapper
		);

		$result = $instance->computeTableRowDiff(
			42,
			$semanticData
		);

		$this->assertInternalType(
			'array',
			$result
		);
	}

	public function testChangeOp() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );
		$semanticData = new SemanticData( $subject );

		$propertyTables = [];

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->setMethods( [ 'getPropertyTables' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( $propertyTables ) );

		$instance = new PropertyTableRowDiffer(
			$store,
			$this->propertyTableRowMapper
		);

		$instance->setChangeOp( new ChangeOp( $subject ) );

		$result = $instance->computeTableRowDiff(
			42,
			$semanticData
		);

		$this->assertInstanceOf(
			'\SMW\SQLStore\ChangeOp\ChangeOp',
			$instance->getChangeOp()
		);
	}

	public function testChangeOpWithUnknownFixedProperty() {

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

		$subject = new DIWikiPage( 'Foo', NS_MAIN );
		$semanticData = new SemanticData( $subject );

		$propertyTables = [ $propertyTable ];

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->setMethods( [ 'getPropertyTables' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( $propertyTables ) );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( $propertyTables ) );

		$instance = new PropertyTableRowDiffer(
			$store,
			$this->propertyTableRowMapper
		);

		$instance->setChangeOp( new ChangeOp( $subject ) );

		$result = $instance->computeTableRowDiff(
			42,
			$semanticData
		);

		$this->assertInstanceOf(
			'\SMW\SQLStore\ChangeOp\ChangeOp',
			$instance->getChangeOp()
		);

		$this->assertEmpty(
			$instance->getChangeOp()->getFixedPropertyRecords()
		);
	}

}
