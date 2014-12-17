<?php

namespace SMW\Tests\SQLStore\Writer;

use \SMWSQLStore3Writers;

use Title;

/**
 * @covers \SMWSQLStore3Writers
 *
 * @group SMW
 * @group SMWExtension
 *
 * @group semantic-mediawiki-sqlstore
 * @group mediawiki-databaseless
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class DeleteSubjectTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$parentStore = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMWSQLStore3Writers',
			new SMWSQLStore3Writers( $parentStore )
		);
	}

	public function testDeleteSubjectForMainNamespace() {

		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$objectIdGenerator = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$objectIdGenerator->expects( $this->once() )
			->method( 'getSMWPageIDandSort' )
			->will( $this->returnValue( 0 ) );

		$objectIdGenerator->expects( $this->once() )
			->method( 'makeSMWPageID' )
			->will( $this->returnValue( 0 ) );

		$objectIdGenerator->expects( $this->once() )
			->method( 'getPropertyTableHashes' )
			->will( $this->returnValue( array() ) );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( array() ) );

		$parentStore = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$parentStore->expects( $this->exactly( 5 ) )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIdGenerator ) );

		$parentStore->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$parentStore->expects( $this->exactly( 4 ) )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array() ) );

		$instance = new SMWSQLStore3Writers( $parentStore );
		$instance->deleteSubject( $title );
	}

	public function testDeleteSubjectForConceptNamespace() {

		$title = Title::newFromText( __METHOD__, SMW_NS_CONCEPT );

		$objectIdGenerator = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$objectIdGenerator->expects( $this->once() )
			->method( 'getSMWPageID' )
			->with(
				$this->equalTo( $title->getDBkey() ),
				$this->equalTo( $title->getNamespace() ),
				$this->equalTo( $title->getInterwiki() ),
				'',
				false )
			->will( $this->returnValue( 0 ) );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( array() ) );

		$database->expects( $this->atLeastOnce() )
			->method( 'selectRow' )
			->will( $this->returnValue( false ) );

		$database->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->will( $this->returnValue( true ) );

		$parentStore = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$parentStore->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$parentStore->expects( $this->exactly( 6 ) )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $objectIdGenerator ) );

		$parentStore->expects( $this->exactly( 4 ) )
			->method( 'getPropertyTables' )
			->will( $this->returnValue( array() ) );

		$instance = new SMWSQLStore3Writers( $parentStore );
		$instance->deleteSubject( $title );
	}

}
