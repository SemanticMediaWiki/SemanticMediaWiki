<?php

namespace SMW\Tests\SQLStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\PropertyTableIdReferenceFinder;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SQLStore\PropertyTableIdReferenceFinder
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class PropertyTableIdReferenceFinderTest extends TestCase {

	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PropertyTableIdReferenceFinder::class,
			new PropertyTableIdReferenceFinder( $this->store )
		);
	}

	public function testFindAtLeastOneActiveReferenceById() {
		$tableDefinition = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition->expects( $this->atLeastOnce() )
			->method( 'getFields' )
			->willReturn( [ 'o_id' => 42 ] );

		$connection = $this->getMockBuilder( Database::class )
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
		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition = $connection = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition->expects( $this->once() )
			->method( 'usesIdSubject' )
			->willReturn( true );

		$connection = $this->getMockBuilder( Database::class )
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

		$instance->tryToFindAtLeastOneReferenceForProperty( new Property( 'Foo' ) );
	}

	public function testHasResidualPropertyTableReference() {
		$connection = $this->getMockBuilder( Database::class )
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
		$connection = $this->getMockBuilder( Database::class )
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
		$connection = $this->getMockBuilder( Database::class )
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
