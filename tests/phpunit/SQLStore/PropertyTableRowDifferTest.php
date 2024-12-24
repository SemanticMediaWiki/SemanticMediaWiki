<?php

namespace SMW\Tests\SQLStore;

use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\SQLStore\PropertyTableRowDiffer;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\PropertyTableRowDiffer
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class PropertyTableRowDifferTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $propertyTableRowMapper;

	protected function setUp(): void {
		$this->propertyTableRowMapper = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableRowMapper' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyTableRowMapper->expects( $this->any() )
			->method( 'mapToRows' )
			->willReturn( [ [], [], [], [] ] );
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
			->onlyMethods( [ 'getPropertyTables' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( $propertyTables );

		$instance = new PropertyTableRowDiffer(
			$store,
			$this->propertyTableRowMapper
		);

		$result = $instance->computeTableRowDiff(
			42,
			$semanticData
		);

		$this->assertIsArray(

			$result
		);
	}

	public function testChangeOp() {
		$subject = new DIWikiPage( 'Foo', NS_MAIN );
		$semanticData = new SemanticData( $subject );

		$propertyTables = [];

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->onlyMethods( [ 'getPropertyTables' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( $propertyTables );

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
			->willReturn( true );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->willReturn( true );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getFixedProperty' )
			->willReturn( '_UNKNOWN_FIXED_PROPERTY' );

		$subject = new DIWikiPage( 'Foo', NS_MAIN );
		$semanticData = new SemanticData( $subject );

		$propertyTables = [ $propertyTable ];

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->onlyMethods( [ 'getPropertyTables' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( $propertyTables );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( $propertyTables );

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

	public function testChangeOpWithUnknownFixedProperty_Ghost() {
		$row = [
			'smw_proptable_hash' => null
		];

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( [] );

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->willReturn( (object)$row );

		$propertyTable = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'isFixedPropertyTable' )
			->willReturn( true );

		$propertyTable->expects( $this->atLeastOnce() )
			->method( 'getFixedProperty' )
			->willReturn( '_UNKNOWN_FIXED_PROPERTY' );

		$subject = new DIWikiPage( 'Foo', NS_MAIN );
		$semanticData = new SemanticData( $subject );

		$propertyTables = [ $propertyTable ];

		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getPropertyTableHashes' )
			->willReturn( [ 'foo' => 'abcdef10001' ] );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'getPropertyTables', 'getConnection', 'getObjectIds' ] )
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( $propertyTables );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( $propertyTables );

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$instance = new PropertyTableRowDiffer(
			$store,
			$this->propertyTableRowMapper
		);

		$instance->setChangeOp( new ChangeOp( $subject ) );
		$instance->checkRemnantEntities( true );

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
