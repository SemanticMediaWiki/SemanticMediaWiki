<?php

namespace SMW\Tests\Unit\SQLStore\Lookup;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\MediaWiki\Connection\Database;
use SMW\RequestOptions;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\Lookup\ProximityPropertyValueLookup;
use SMW\SQLStore\SQLStore;
use stdClass;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @covers \SMW\SQLStore\Lookup\ProximityPropertyValueLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ProximityPropertyValueLookupTest extends TestCase {

	public function testCanConstruct() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ProximityPropertyValueLookup::class,
			new ProximityPropertyValueLookup( $store )
		);
	}

	public function testLookup_wpg_property() {
		$row = new stdClass;
		$row->smw_title = 'Test';
		$row->smw_id = 42;

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$whereConditions = [];

		$connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( function () use ( $row, &$whereConditions ) {
				return $this->createMockSelectQueryBuilder(
					[ $row ],
					$whereConditions
				);
			} );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'getSMWPropertyID', 'isFixedPropertyTable' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 42 );

		$idTable->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->willReturn( false );

		$dataItemHandler = $this->getMockBuilder( DataItemHandler::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$store->expects( $this->any() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new ProximityPropertyValueLookup(
			$store
		);

		$instance->lookup(
			new Property( 'Bar' ),
			'Foo',
			new RequestOptions()
		);

		// The wpg path goes through fetchFromIDTable -> build_like which
		// produces an OR-joined LIKE clause on smw_sortkey covering the search
		// term, its ucfirst form, and uppercase form (and lowercase if input
		// is mixed case).
		$flat = $this->flattenWhereConditions( $whereConditions );

		$this->assertContains(
			'(smw_sortkey LIKE %Foo% OR smw_sortkey LIKE %Foo% OR smw_sortkey LIKE %FOO%)',
			$flat
		);
	}

	public function testLookup_txt_property() {
		$row = new stdClass;
		$row->o_hash = 'Test';
		$row->smw_id = 42;

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$whereConditions = [];

		$connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( function () use ( $row, &$whereConditions ) {
				return $this->createMockSelectQueryBuilder(
					[ $row ],
					$whereConditions
				);
			} );

		$idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'getSMWPropertyID', 'isFixedPropertyTable' ] )
			->getMock();

		$idTable->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturn( 42 );

		$idTable->expects( $this->any() )
			->method( 'isFixedPropertyTable' )
			->willReturn( false );

		$dataItemHandler = $this->getMockBuilder( DataItemHandler::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$dataItemHandler->expects( $this->any() )
			->method( 'getLabelField' )
			->willReturn( 'o_hash' );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

		$store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$store->expects( $this->any() )
			->method( 'getDataItemHandlerForDIType' )
			->willReturn( $dataItemHandler );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new ProximityPropertyValueLookup(
			$store
		);

		$instance->lookup(
			new Property( '_TEXT' ),
			'Foo',
			new RequestOptions()
		);

		$flat = $this->flattenWhereConditions( $whereConditions );

		$this->assertContains(
			'(o_hash LIKE %Foo% OR o_hash LIKE %Foo% OR o_hash LIKE %FOO%)',
			$flat
		);

		$this->assertContains(
			[ 'p_id' => 42 ],
			$whereConditions
		);
	}

	/**
	 * Flatten a list of captured andWhere() arguments into a list of strings
	 * for easy substring assertions.
	 */
	private function flattenWhereConditions( array $conditions ): array {
		$flat = [];
		foreach ( $conditions as $cond ) {
			if ( is_string( $cond ) ) {
				$flat[] = $cond;
			} elseif ( is_array( $cond ) ) {
				foreach ( $cond as $k => $v ) {
					$flat[] = is_int( $k ) ? (string)$v : "$k=$v";
				}
			}
		}
		return $flat;
	}

	/**
	 * Creates a mock SelectQueryBuilder where chained methods return $this,
	 * fetchResultSet() returns the given rows wrapped in FakeResultWrapper,
	 * and andWhere() arguments are captured into $whereConditions for
	 * inspection by tests.
	 */
	private function createMockSelectQueryBuilder(
		array $rows = [],
		array &$whereConditions = []
	) {
		$queryBuilder = $this->getMockBuilder( SelectQueryBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$chainMethods = [ 'select', 'fields', 'field', 'from', 'table',
			'join', 'leftJoin', 'where', 'groupBy', 'having', 'orderBy',
			'caller', 'distinct', 'limit', 'offset', 'options', 'option' ];

		foreach ( $chainMethods as $method ) {
			$queryBuilder->expects( $this->any() )
				->method( $method )
				->willReturnSelf();
		}

		$queryBuilder->expects( $this->any() )
			->method( 'andWhere' )
			->willReturnCallback( static function ( $conds ) use ( $queryBuilder, &$whereConditions ) {
				$whereConditions[] = $conds;
				return $queryBuilder;
			} );

		$queryBuilder->expects( $this->any() )
			->method( 'newSubquery' )
			->willReturnCallback( fn () => $this->createMockSelectQueryBuilder(
				$rows,
				$whereConditions
			) );

		$queryBuilder->expects( $this->any() )
			->method( 'fetchResultSet' )
			->willReturn( new FakeResultWrapper( $rows ) );

		return $queryBuilder;
	}

}
