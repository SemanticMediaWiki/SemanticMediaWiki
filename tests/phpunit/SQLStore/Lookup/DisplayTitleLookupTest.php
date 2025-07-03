<?php

namespace SMW\Tests\SQLStore\Lookup;

use SMW\DIWikiPage;
use SMW\SQLStore\Lookup\DisplayTitleLookup;

/**
 * @covers \SMW\SQLStore\Lookup\DisplayTitleLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   3.1
 *
 * @author mwjames
 */
class DisplayTitleLookupTest extends \PHPUnit\Framework\TestCase {

	private $store;

	protected function setUp(): void {
		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DisplayTitleLookup::class,
			new DisplayTitleLookup( $this->store )
		);
	}

	public function testPrefetchFromList() {
		$subjects = [
			DIWikiPage::newFromText( 'Foo' ),
			DIWikiPage::newFromText( 'Bar' )
		];

		$rows = [
			(object)[ 's_id' => 42, 'o_blob' => null, 'o_hash' => '123' ],
			(object)[ 's_id' => 1001, 'o_blob' => 'abc_blob', 'o_hash' => 'abc_hash' ]
		];

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$idTable = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getId' )
			->willReturnOnConsecutiveCalls( 42, 1001 );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Connection\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'unescape_bytea' )
			->willReturnArgument( 0 );

		$connection->expects( $this->exactly( 2 ) )
			->method( 'select' )
			->willReturnCallback( static function ( $table ) use ( $rows ) {
				if ( $table === 'smw_object_ids' ) {
					return [
						(object)[ 'smw_hash' => 'ebb1b47f7cf43a5a58d3c6cc58f3c3bb8b9246e6', 'smw_id' => 42 ],
						(object)[ 'smw_hash' => '7b6b944694382bfab461675f40a2bda7e71e68e3', 'smw_id' => 1001 ]
					];
				} elseif ( $table === 'foo_table' ) {
					return $rows;
				}
				return [];
			} );

		$tableDefinition = $this->getMockBuilder( '\SMW\SQLStore\PropertyTableDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$tableDefinition->expects( $this->any() )
			->method( 'getName' )
			->willReturn( 'foo_table' );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->store->expects( $this->any() )
			->method( 'findPropertyTableID' )
			->willReturn( 'Foo' );

		$this->store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [ 'Foo' => $tableDefinition ] );

		$instance = new DisplayTitleLookup(
			$this->store
		);

		$list = [
			$subjects[0]->getSha1() => $subjects[0],
			$subjects[1]->getSha1() => $subjects[1]
		];

		$this->assertEquals(
			[
				$subjects[0]->getSha1() => '123',
				$subjects[1]->getSha1() => 'abc_blob'
			],
			$instance->prefetchFromList( $list )
		);
	}

}
