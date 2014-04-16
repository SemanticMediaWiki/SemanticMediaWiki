<?php

namespace SMW\Tests\SQLStore;

use SMW\Tests\Util\Mock\MockDBConnectionProvider;
use SMW\MediaWiki\Database;

use SMW\DIProperty;
use SMWSql3SmwIds;

/**
 * @covers \SMWSql3SmwIds
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group SQLStore
 * @group MockTest
 *
 * @license GNU GPL v2+
 * @since 1.9.1
 *
 * @author mwjames
 */
class SQLStoreSmwIdsTest extends \PHPUnit_Framework_TestCase {

	public function getClass() {
		return '\SMWSql3SmwIds';
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf( $this->getClass(), new SMWSql3SmwIds( $store ) );
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testGetPropertyId() {

		$selectRow = new \stdClass;
		$selectRow->smw_id = 9999;
		$selectRow->smw_sortkey = 'Foo';

		$readConnection = new MockDBConnectionProvider();
		$mockDatabase = $readConnection->getMockDatabase();

		$mockDatabase->expects( $this->once() )
			->method( 'selectRow' )
			->will( $this->returnValue( $selectRow ) );

		$database = new Database( $readConnection, new MockDBConnectionProvider );

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getDatabase' )
			->will( $this->returnValue( $database ) );

		$instance = new SMWSql3SmwIds( $store );

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

		$readConnection = new MockDBConnectionProvider();
		$mockDatabase = $readConnection->getMockDatabase();

		$mockDatabase->expects( $this->once() )
			->method( 'selectRow' )
			->will( $this->returnValue( $selectRow ) );

		$database = new Database( $readConnection, new MockDBConnectionProvider );

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getDatabase' )
			->will( $this->returnValue( $database ) );

		$instance = new SMWSql3SmwIds( $store );

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

		$readConnection = new MockDBConnectionProvider();
		$mockReadDatabase = $readConnection->getMockDatabase();

		$mockReadDatabase->expects( $this->any() )
			->method( 'selectRow' )
			->will( $this->returnValue( $selectRow ) );

		$writeConnection = new MockDBConnectionProvider();
		$mockWriteDatabase = $writeConnection->getMockDatabase();

		$mockWriteDatabase->expects( $this->once() )
			->method( 'insertId' )
			->will( $this->returnValue( 9999 ) );

		$database = new Database( $readConnection, $writeConnection );

		$store = $this->getMockBuilder( 'SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getDatabase' )
			->will( $this->returnValue( $database ) );

		$instance = new SMWSql3SmwIds( $store );

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
