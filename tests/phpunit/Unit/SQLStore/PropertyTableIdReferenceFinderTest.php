<?php

namespace SMW\Tests\SQLStore;

use SMW\DIProperty;
use SMW\SQLStore\PropertyTableIdReferenceFinder;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SQLStore\PropertyTableIdReferenceFinder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class PropertyTableIdReferenceFinderTest extends \PHPUnit_Framework_TestCase {

	private $store;

	protected function setUp() {
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

	public function testTryToFindAtLeastOneReferenceForId() {

		$tableDefinition = $connection = $this->getMockBuilder( '\SMW\SQLStore\TableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition->expects( $this->atLeastOnce() )
			->method( 'getFields' )
			->will( $this->returnValue( array( 'o_id' => 42 ) ) );

		$PropertyTableIdReferenceFinder = $connection = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableIdReferenceFinder' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->will( $this->returnValue( false ) );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( $tableDefinition ) ) );

		$instance = new PropertyTableIdReferenceFinder(
			$this->store
		);

		$instance->tryToFindAtLeastOneReferenceForId( 42 );
	}

	public function testTryToFindAtLeastOneReferenceForProperty() {

		$idTable = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

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

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $idTable ) );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array( $tableDefinition ) ) );

		$instance = new PropertyTableIdReferenceFinder(
			$this->store
		);

		$instance->tryToFindAtLeastOneReferenceForProperty( new DIProperty( 'Foo' ) );
	}

	public function testHasResidualReferenceFor() {

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array() ) );

		$instance = new PropertyTableIdReferenceFinder(
			$this->store
		);

		$this->assertInternalType(
			'boolean',
			$instance->hasResidualReferenceFor( 42 )
		);
	}

	public function testConfirmBorderId() {

		$instance = new PropertyTableIdReferenceFinder(
			$this->store
		);

		$this->assertTrue(
			$instance->hasResidualReferenceFor( SQLStore::FIXED_PROPERTY_ID_UPPERBOUND )
		);
	}

}
