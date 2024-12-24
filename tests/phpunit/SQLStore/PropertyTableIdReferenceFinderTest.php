<?php

namespace SMW\Tests\SQLStore;

use SMW\DIProperty;
use SMW\SQLStore\PropertyTableIdReferenceFinder;
use SMW\SQLStore\SQLStore;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\PropertyTableIdReferenceFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class PropertyTableIdReferenceFinderTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\SQLStore\PropertyTableIdReferenceFinder',
			new PropertyTableIdReferenceFinder( $this->store )
		);
	}

	public function testFindAtLeastOneActiveReferenceById() {
		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition->expects( $this->atLeastOnce() )
			->method( 'getFields' )
			->willReturn( [ 'o_id' => 42 ] );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->willReturn( false );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ $tableDefinition ] );

		$instance = new PropertyTableIdReferenceFinder(
			$this->store
		);

		$this->assertFalse(
			$instance->findAtLeastOneActiveReferenceById( 42 )
		);
	}

	public function testTryToFindAtLeastOneReferenceForProperty() {
		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition = $connection = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition->expects( $this->once() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->willReturn( false );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ $tableDefinition ] );

		$instance = new PropertyTableIdReferenceFinder(
			$this->store
		);

		$instance->tryToFindAtLeastOneReferenceForProperty( new DIProperty( 'Foo' ) );
	}

	public function testHasResidualPropertyTableReference() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->willReturn( false );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = new PropertyTableIdReferenceFinder(
			$this->store
		);

		$this->assertIsBool(

			$instance->hasResidualPropertyTableReference( 42 )
		);
	}

	public function testHasResidualReferenceFor() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->willReturn( false );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = new PropertyTableIdReferenceFinder(
			$this->store
		);

		$this->assertIsBool(

			$instance->hasResidualReferenceForId( 42 )
		);
	}

	public function testSearchAllTablesToFindAtLeastOneReferenceById() {
		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->willReturn( false );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$instance = new PropertyTableIdReferenceFinder(
			$this->store
		);

		$this->assertIsArray(

			$instance->searchAllTablesToFindAtLeastOneReferenceById( 42 )
		);
	}

	public function testConfirmBorderId() {
		$instance = new PropertyTableIdReferenceFinder(
			$this->store
		);

		$this->assertTrue(
			$instance->hasResidualReferenceForId( SQLStore::FIXED_PROPERTY_ID_UPPERBOUND )
		);
	}

}
