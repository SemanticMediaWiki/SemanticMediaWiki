<?php

namespace SMW\Tests\SQLStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMWSql3SmwIds;

/**
 * @covers \SMWSql3SmwIds
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class SQLStoreSmwIdsTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $idToDataItemMatchFinder;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->idToDataItemMatchFinder = $this->getMockBuilder( '\SMW\SQLStore\IdToDataItemMatchFinder' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->assertInstanceOf(
			'\SMWSql3SmwIds',
			new SMWSql3SmwIds( $store, $this->idToDataItemMatchFinder )
		);
	}

	public function testRedirectInfoRoundtrip() {

		$subject = new DIWikiPage( 'Foo', 9001 );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new SMWSql3SmwIds(
			$store,
			$this->idToDataItemMatchFinder
		);

		$this->assertFalse(
			$instance->checkIsRedirect( $subject )
		);

		$instance->addRedirectForId( 42, 'Foo', 9001 );

		$this->assertEquals(
			42,
			$instance->findRedirectIdFor( 'Foo', 9001 )
		);

		$this->assertTrue(
			$instance->checkIsRedirect( $subject )
		);

		$instance->deleteRedirectEntry( 'Foo', 9001 );

		$this->assertEquals(
			0,
			$instance->findRedirectIdFor( 'Foo', 9001 )
		);

		$this->assertFalse(
			$instance->checkIsRedirect( $subject )
		);
	}

	public function testGetPropertyId() {

		$selectRow = new \stdClass;
		$selectRow->smw_id = 9999;
		$selectRow->smw_sortkey = 'Foo';

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->will( $this->returnValue( $selectRow ) );

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new SMWSql3SmwIds(
			$store,
			$this->idToDataItemMatchFinder
		);

		$result = $instance->getSMWPropertyID( new DIProperty( 'Foo' ) );

		$this->assertEquals( 9999, $result );
	}

	/**
	 * @dataProvider pageIdandSortProvider
	 */
	public function testGetSMWPageIDandSort( $parameters ) {

		$selectRow = new \stdClass;
		$selectRow->smw_id = 9999;
		$selectRow->smw_sortkey = 'Foo';
		$selectRow->smw_proptable_hash = serialize( 'Foo' );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->will( $this->returnValue( $selectRow ) );

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new SMWSql3SmwIds(
			$store,
			$this->idToDataItemMatchFinder
		);

		$sortkey = $parameters['sortkey'];

		$result  = $instance->getSMWPageIDandSort(
			$parameters['title'],
			$parameters['namespace'],
			$parameters['iw'],
			$parameters['subobjectName'],
			$sortkey, // pass-by-reference
			$parameters['canonical'],
			$parameters['fetchHashes']
		);

		$this->assertEquals( 9999, $result );
	}

	/**
	 * @dataProvider pageIdandSortProvider
	 */
	public function testMakeSMWPageID( $parameters ) {

		$selectRow = new \stdClass;
		$selectRow->smw_id = 0;
		$selectRow->o_id = 0;
		$selectRow->smw_sortkey = 'Foo';
		$selectRow->smw_proptable_hash = serialize( 'Foo' );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->will( $this->returnValue( $selectRow ) );

		$connection->expects( $this->once() )
			->method( 'insertId' )
			->will( $this->returnValue( 9999 ) );

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new SMWSql3SmwIds(
			$store,
			$this->idToDataItemMatchFinder
		);

		$sortkey = $parameters['sortkey'];

		$result  = $instance->makeSMWPageID(
			$parameters['title'],
			$parameters['namespace'],
			$parameters['iw'],
			$parameters['subobjectName'],
			$sortkey,
			$parameters['canonical'],
			$parameters['fetchHashes']
		);

		$this->assertEquals( 9999, $result );
	}

	public function testGetDataItemById() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->idToDataItemMatchFinder->expects( $this->once() )
			->method( 'getDataItemById' )
			->with( $this->equalTo( 42 ) )
			->will( $this->returnValue( new DIWikiPage( 'Foo', NS_MAIN ) ) );

		$instance = new SMWSql3SmwIds(
			$this->store,
			$this->idToDataItemMatchFinder
		);

		$this->assertInstanceOf(
			'\SMW\DIWikiPage',
			$instance->getDataItemById( 42 )
		);
	}

	public function testUpdateInterwikiField() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->anything(),
				$this->equalTo( array( 'smw_iw' => 'Bar' ) ),
				$this->equalTo( array( 'smw_id' => 42 ) ) );

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new SMWSql3SmwIds(
			$store,
			$this->idToDataItemMatchFinder
		);

		$instance->updateInterwikiField(
			42,
			new DIWikiPage( 'Foo', NS_MAIN, 'Bar' )
		);
	}

	public function testGetIDOnPredefinedProperty() {

		$row = new \stdClass;
		$row->smw_id = 42;

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->once() )
			->method( 'selectRow' )
			->will( $this->returnValue( $row ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new SMWSql3SmwIds(
			$store,
			$this->idToDataItemMatchFinder
		);

		$this->assertEquals(
			29,
			$instance->getIDFor( new DIWikiPage( '_MDAT', SMW_NS_PROPERTY ) )
		);

		$this->assertEquals(
			42,
			$instance->getIDFor( new DIWikiPage( '_MDAT', SMW_NS_PROPERTY, '', 'Foo' ) )
		);
	}

	public function pageIdandSortProvider() {

		$provider[] = array( 'Foo', NS_MAIN, '' , '', 'FOO', false, false );
		$provider[] = array( 'Foo', NS_MAIN, '' , '', 'FOO', true, false );
		$provider[] = array( 'Foo', NS_MAIN, '' , '', 'FOO', true, true );
		$provider[] = array( 'Foo', NS_MAIN, 'quy' , '', 'FOO', false, false );
		$provider[] = array( 'Foo', NS_MAIN, 'quy' , 'xwoo', 'FOO', false, false );

		$provider[] = array( 'pro', SMW_NS_PROPERTY, '' , '', 'PRO', false, false );
		$provider[] = array( 'pro', SMW_NS_PROPERTY, '' , '', 'PRO', true, false );
		$provider[] = array( 'pro', SMW_NS_PROPERTY, '' , '', 'PRO', true, true );

		return $this->createAssociativeArrayFromProviderDefinition( $provider );
	}

	private function createAssociativeArrayFromProviderDefinition( $definitions ) {

		foreach ( $definitions as $map ) {
			$provider[] = array( array(
				'title'         => $map[0],
				'namespace'     => $map[1],
				'iw'            => $map[2],
				'subobjectName' => $map[3],
				'sortkey'       => $map[4],
				'canonical'     => $map[5],
				'fetchHashes'   => $map[6]
			) );
		}

		return $provider;
	}

}
