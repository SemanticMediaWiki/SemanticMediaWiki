<?php

namespace SMW\Tests\SQLStore;

use SMW\SQLStore\PropertyTableOutdatedReferenceDisposer;
use Title;

/**
 * @covers \SMW\SQLStore\PropertyTableOutdatedReferenceDisposer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class PropertyTableOutdatedReferenceDisposerTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp() {
		parent::setUp();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\SQLStore\PropertyTableOutdatedReferenceDisposer',
			new PropertyTableOutdatedReferenceDisposer( $this->store )
		);
	}

	public function testTryToRemoveOutdatedEntryFromIDTable() {

		$tableDefinition = $connection = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->will( $this->returnValue( false ) );

		$connection->expects( $this->once() )
			->method( 'delete' )
			->with( $this->equalTo( \SMW\SQLStore\SQLStore::ID_TABLE ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( $tableDefinition ) ) );

		$instance = new PropertyTableOutdatedReferenceDisposer(
			$this->store
		);

		$instance->attemptToRemoveOutdatedEntryFromIDTable( 42 );
	}

	public function testDeleteReferencesFromPropertyTablesFor() {

		$tableDefinition = $connection = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition->expects( $this->once() )
			->method( 'usesIdSubject' )
			->will( $this->returnValue( true ) );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->will( $this->returnValue( false ) );

		$connection->expects( $this->at( 1 ) )
			->method( 'delete' )
			->with( $this->equalTo( \SMW\SQLStore\SQLStore::ID_TABLE ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( $tableDefinition ) ) );

		$instance = new PropertyTableOutdatedReferenceDisposer(
			$this->store
		);

		$instance->removeAnyReferenceFromPropertyTablesFor( 42 );
	}

}
