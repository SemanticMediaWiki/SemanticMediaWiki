<?php

namespace SMW\Tests\SQLStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\ChangeOp\ChangeOp;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\PropertyTableRowDiffer;
use SMW\SQLStore\PropertyTableRowMapper;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @covers \SMW\SQLStore\PropertyTableRowDiffer
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @since 2.3
 *
 * @author mwjames
 */
class PropertyTableRowDifferTest extends TestCase {

	private $propertyTableRowMapper;

	protected function setUp(): void {
		$this->propertyTableRowMapper = $this->getMockBuilder( PropertyTableRowMapper::class )
			->disableOriginalConstructor()
			->getMock();

		$this->propertyTableRowMapper->expects( $this->any() )
			->method( 'mapToRows' )
			->willReturn( [ [], [], [], [] ] );
	}

	public function testCanConstruct() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			PropertyTableRowDiffer::class,
			new PropertyTableRowDiffer( $store, $this->propertyTableRowMapper )
		);
	}

	public function testComputeTableRowDiffForEmptyPropertyTables() {
		$subject = new WikiPage( 'Foo', NS_MAIN );
		$semanticData = new SemanticData( $subject );

		$propertyTables = [];

		$store = $this->getMockBuilder( SQLStore::class )
			->setMethods( [ 'getPropertyTables' ] )
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
		$subject = new WikiPage( 'Foo', NS_MAIN );
		$semanticData = new SemanticData( $subject );

		$propertyTables = [];

		$store = $this->getMockBuilder( SQLStore::class )
			->setMethods( [ 'getPropertyTables' ] )
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
			ChangeOp::class,
			$instance->getChangeOp()
		);
	}

	public function testChangeOpWithUnknownFixedProperty() {
		$propertyTable = $this->getMockBuilder( PropertyTableDefinition::class )
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

		$subject = new WikiPage( 'Foo', NS_MAIN );
		$semanticData = new SemanticData( $subject );

		$propertyTables = [ $propertyTable ];

		$store = $this->getMockBuilder( SQLStore::class )
			->setMethods( [ 'getPropertyTables' ] )
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
			ChangeOp::class,
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

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( [] );

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->willReturn( (object)$row );

		$propertyTable = $this->getMockBuilder( PropertyTableDefinition::class )
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

		$subject = new WikiPage( 'Foo', NS_MAIN );
		$semanticData = new SemanticData( $subject );

		$propertyTables = [ $propertyTable ];

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getPropertyTableHashes' )
			->willReturn( [ 'foo' => 'abcdef10001' ] );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getPropertyTables', 'getConnection', 'getObjectIds' ] )
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
			ChangeOp::class,
			$instance->getChangeOp()
		);

		$this->assertEmpty(
			$instance->getChangeOp()->getFixedPropertyRecords()
		);
	}

}
