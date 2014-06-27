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
class SqlStoreWriterDataUpdateTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$parentStore = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMWSQLStore3Writers',
			new SMWSQLStore3Writers( $parentStore )
		);
	}

	public function testDoDataUpdateForMainNamespaceWithoutSubobject() {

		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->setConstructorArgs( array( DIWikiPage::newFromTitle( $title ) ) )
			->setMethods( null )
			->getMock();

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

		$database->expects( $this->once() )
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
		$instance->doDataUpdate( $semanticData );
	}

	public function testDoDataUpdateForConceptNamespaceWithoutSubobject() {

		$title = Title::newFromText( __METHOD__, SMW_NS_CONCEPT );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->setConstructorArgs( array( DIWikiPage::newFromTitle( $title ) ) )
			->setMethods( null )
			->getMock();

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
		$instance->doDataUpdate( $semanticData );
	}

	public function testDoDataUpdateForMainNamespaceWithRedirect() {

		$title = Title::newFromText( __METHOD__, NS_MAIN );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->setConstructorArgs( array( DIWikiPage::newFromTitle( $title ) ) )
			->setMethods( array( 'getPropertyValues' ) )
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( array( DIWikiPage::newFromTitle( $title ) ) ) );

		$objectIdGenerator = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$objectIdGenerator->expects( $this->once() )
			->method( 'getSMWPageIDandSort' )
			->will( $this->returnValue( 0 ) );

		$objectIdGenerator->expects( $this->once() )
			->method( 'makeSMWPageID' )
			->will( $this->returnValue( 0 ) );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->once() )
			->method( 'select' )
			->will( $this->returnValue( array() ) );

		$database->expects( $this->once() )
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

		$instance = new SMWSQLStore3Writers( $parentStore );
		$instance->doDataUpdate( $semanticData );
	}

}
