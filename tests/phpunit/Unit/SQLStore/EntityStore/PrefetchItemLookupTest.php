<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SequenceMap;
use SMW\MediaWiki\LinkBatch;
use SMW\RequestOptions;
use SMW\SQLStore\EntityStore\CachingSemanticDataLookup;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\EntityStore\PrefetchItemLookup;
use SMW\SQLStore\EntityStore\PropertySubjectsLookup;
use SMW\SQLStore\PropertyTableDefinition;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SQLStore\EntityStore\PrefetchItemLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class PrefetchItemLookupTest extends TestCase {

	private $store;
	private $semanticDataLookup;
	private $propertySubjectsLookup;
	private $requestOptions;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->semanticDataLookup = $this->getMockBuilder( CachingSemanticDataLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->propertySubjectsLookup = $this->getMockBuilder( PropertySubjectsLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$this->requestOptions = $this->getMockBuilder( RequestOptions::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			PrefetchItemLookup::class,
			new PrefetchItemLookup( $this->store, $this->semanticDataLookup, $this->propertySubjectsLookup )
		);
	}

	public function testGetPropertyValues() {
		$subjects = [
			WikiPage::newFromText( __METHOD__ ),
		];

		$expected = [
			WikiPage::newFromText( 'Bar_1' ),
			WikiPage::newFromText( 'Bar_2' ),
			WikiPage::newFromText( 'Bar_3' )
		];

		$linkBatch = $this->getMockBuilder( LinkBatch::class )
			->disableOriginalConstructor()
			->getMock();

		$sequenceMap = $this->getMockBuilder( SequenceMap::class )
			->disableOriginalConstructor()
			->getMock();

		$sequenceMap->expects( $this->atLeastOnce() )
			->method( 'hasSequenceMap' )
			->willReturn( true );

		$entityIdManager = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->atLeastOnce() )
			->method( 'getSequenceMap' )
			->willReturn( [] );

		$diHandler = $this->getMockBuilder( DataItemHandler::class )
			->disableOriginalConstructor()
			->getMock();

		$diHandler->expects( $this->atLeastOnce() )
			->method( 'newFromDBKeys' )
			->willReturnOnConsecutiveCalls( $expected[0], $expected[1], $expected[2] );

		$propertyTableDef = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'smw_foo' => $propertyTableDef ] );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $diHandler );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'findPropertyTableID' )
			->willReturn( 'smw_foo' );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->willReturn( $entityIdManager );

		$this->semanticDataLookup->expects( $this->atLeastOnce() )
			->method( 'prefetchDataFromTable' )
			->willReturn( [ 42 => [ 'Bar_1#0##', 'Bar_2#0##', 'Bar_3#0##' ] ] );

		$instance = new PrefetchItemLookup(
			$this->store,
			$this->semanticDataLookup,
			$this->propertySubjectsLookup,
			$linkBatch,
			$sequenceMap
		);

		$requestOptions = new RequestOptions();

		$requestOptions->setLimit( 1 );
		$requestOptions->setLookahead( 1 );

		$res = $instance->getPropertyValues( $subjects, new Property( 'Foo' ), $requestOptions );

		$this->assertCount(
			2,
			$res[42]
		);
	}

	public function testGetPropertyValues_InvertedProperty() {
		$subjects = [
			WikiPage::newFromText( __METHOD__ ),
		];

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$propertyTableDef = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'smw_foo' => $propertyTableDef ] );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'findPropertyTableID' )
			->willReturn( 'smw_foo' );

		$this->propertySubjectsLookup->expects( $this->atLeastOnce() )
			->method( 'prefetchFromTable' )
			->willReturn( [ 42 => [ 'Bar#0##' ] ] );

		$instance = new PrefetchItemLookup(
			$this->store,
			$this->semanticDataLookup,
			$this->propertySubjectsLookup
		);

		$res = $instance->getPropertyValues( $subjects, new Property( 'Foo', true ), $this->requestOptions );

		$this->assertEquals(
			[
				42 => [ 'Bar#0##' ]
			],
			$res
		);
	}

	public function testGetPropertyValues_InvertedProperty_HashIndex() {
		$subjects = [
			WikiPage::newFromText( __METHOD__ ),
		];

		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->atLeastOnce() )
			->method( 'getDataItemById' )
			->with( 42 )
			->willReturn( WikiPage::newFromText( 'ABC' ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$propertyTableDef = $this->getMockBuilder( PropertyTableDefinition::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'smw_foo' => $propertyTableDef ] );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'findPropertyTableID' )
			->willReturn( 'smw_foo' );

		$this->propertySubjectsLookup->expects( $this->atLeastOnce() )
			->method( 'prefetchFromTable' )
			->willReturn( [ 42 => [ 'Bar#0##' ] ] );

		$instance = new PrefetchItemLookup(
			$this->store,
			$this->semanticDataLookup,
			$this->propertySubjectsLookup
		);

		$requestOptions = new RequestOptions();
		$requestOptions->setOption( PrefetchItemLookup::HASH_INDEX, true );

		$res = $instance->getPropertyValues( $subjects, new Property( 'Foo', true ), $requestOptions );

		$this->assertEquals(
			[
				'ABC#0##' => [ 'Bar#0##' ]
			],
			$res
		);
	}

}
