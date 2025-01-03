<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\RequestOptions;
use SMW\SQLStore\EntityStore\PrefetchItemLookup;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\EntityStore\PrefetchItemLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class PrefetchItemLookupTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;
	private $semanticDataLookup;
	private $propertySubjectsLookup;
	private $requestOptions;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->semanticDataLookup = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\CachingSemanticDataLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->propertySubjectsLookup = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\PropertySubjectsLookup' )
			->disableOriginalConstructor()
			->getMock();

		$this->requestOptions = $this->getMockBuilder( '\SMW\RequestOptions' )
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
			DIWikiPage::newFromText( __METHOD__ ),
		];

		$expected = [
			DIWikiPage::newFromText( 'Bar_1' ),
			DIWikiPage::newFromText( 'Bar_2' ),
			DIWikiPage::newFromText( 'Bar_3' )
		];

		$linkBatch = $this->getMockBuilder( '\SMW\MediaWiki\LinkBatch' )
			->disableOriginalConstructor()
			->getMock();

		$sequenceMap = $this->getMockBuilder( '\SMW\DataModel\SequenceMap' )
			->disableOriginalConstructor()
			->getMock();

		$sequenceMap->expects( $this->atLeastOnce() )
			->method( 'hasSequenceMap' )
			->willReturn( true );

		$entityIdManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$entityIdManager->expects( $this->atLeastOnce() )
			->method( 'getSequenceMap' )
			->willReturn( [] );

		$diHandler = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\DataItemHandler' )
			->disableOriginalConstructor()
			->getMock();

		$diHandler->expects( $this->atLeastOnce() )
			->method( 'newFromDBKeys' )
			->willReturnOnConsecutiveCalls( $expected[0], $expected[1], $expected[2] );

		$propertyTableDef = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
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

		$res = $instance->getPropertyValues( $subjects, new DIProperty( 'Foo' ), $requestOptions );

		$this->assertCount(
			2,
			$res[42]
		);
	}

	public function testGetPropertyValues_InvertedProperty() {
		$subjects = [
			DIWikiPage::newFromText( __METHOD__ ),
		];

		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$propertyTableDef = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
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

		$res = $instance->getPropertyValues( $subjects, new DIProperty( 'Foo', true ), $this->requestOptions );

		$this->assertEquals(
			[
				42 => [ 'Bar#0##' ]
			],
			$res
		);
	}

	public function testGetPropertyValues_InvertedProperty_HashIndex() {
		$subjects = [
			DIWikiPage::newFromText( __METHOD__ ),
		];

		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->atLeastOnce() )
			->method( 'getDataItemById' )
			->with( 42 )
			->willReturn( DIWikiPage::newFromText( 'ABC' ) );

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$propertyTableDef = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
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

		$requestOptions = new \SMW\RequestOptions();
		$requestOptions->setOption( PrefetchItemLookup::HASH_INDEX, true );

		$res = $instance->getPropertyValues( $subjects, new DIProperty( 'Foo', true ), $requestOptions );

		$this->assertEquals(
			[
				'ABC#0##' => [ 'Bar#0##' ]
			],
			$res
		);
	}

}
