<?php

namespace SMW\Tests\SQLStore;

use \SMWSQLStore3Writers;
use SMW\SemanticData;
use SMW\DIWikiPage;

use Title;

/**
 * @covers \SMWSQLStore3Writers
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-unit
 * @group semantic-mediawiki-sqlstore
 * @group mediawiki-databaseless
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class SqlStoreWriterChangeTitleTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$parentStore = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMWSQLStore3Writers',
			new SMWSQLStore3Writers( $parentStore )
		);
	}

	public function testChangeTitleForMainNamespaceWithoutRedirectId() {

		$objectIdGenerator = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$objectIdGenerator->expects( $this->at( 0 ) )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 1 ) );

		$objectIdGenerator->expects( $this->at( 1 ) )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 5 ) );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( array() ) );

		$database->expects( $this->once() )
			->method( 'query' )
			->will( $this->returnValue( true ) );

		$database->expects( $this->atLeastOnce() )
			->method( 'selectRow' )
			->will( $this->returnValue( false ) );

		$parentStore = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$parentStore->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIdGenerator ) );

		$parentStore->expects( $this->atLeastOnce() )
			->method( 'getDatabase' )
			->will( $this->returnValue( $database ) );

		$parentStore->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array() ) );

		$instance = new SMWSQLStore3Writers( $parentStore );

		$instance->changeTitle(
			Title::newFromText( __METHOD__ . '-old', NS_MAIN ),
			Title::newFromText( __METHOD__ . '-new', NS_MAIN ),
			9999
		);
	}

	public function testChangeTitleForMainNamespaceWithRedirectId() {

		$row = new \stdClass;
		$row->o_id = 5555;

		$objectIdGenerator = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$objectIdGenerator->expects( $this->at( 0 ) )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 1 ) );

		$objectIdGenerator->expects( $this->at( 1 ) )
			->method( 'getSMWPageID' )
			->will( $this->returnValue( 5 ) );

		$objectIdGenerator->expects( $this->atLeastOnce() )
			->method( 'getSMWPropertyID' )
			->will( $this->returnValue( 88 ) );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->once() )
			->method( 'query' )
			->will( $this->returnValue( true ) );

		$database->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( array() ) );

		$database->expects( $this->atLeastOnce() )
			->method( 'selectRow' )
			->will( $this->returnValue( $row ) );

		$parentStore = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$parentStore->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIdGenerator ) );

		$parentStore->expects( $this->atLeastOnce() )
			->method( 'getDatabase' )
			->will( $this->returnValue( $database ) );

		$parentStore->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array() ) );

		$instance = new SMWSQLStore3Writers( $parentStore );

		$instance->changeTitle(
			Title::newFromText( __METHOD__ . '-old', NS_MAIN ),
			Title::newFromText( __METHOD__ . '-new', NS_MAIN ),
			9999,
			1111
		);
	}

}
