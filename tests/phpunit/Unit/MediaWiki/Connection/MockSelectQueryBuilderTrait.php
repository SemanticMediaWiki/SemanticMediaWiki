<?php

namespace SMW\Tests\Unit\MediaWiki\Connection;

use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * Test helper for mocking `SelectQueryBuilder` chains. Returns a builder mock
 * where every chain method returns the builder itself, where()/andWhere()
 * arguments are captured into a shared array, fetchResultSet() returns the
 * given rows wrapped in FakeResultWrapper, fetchRow() returns the first row
 * (or false if empty), and fetchField() returns the first row's first scalar
 * value. newSubquery() returns a child mock sharing the same captured
 * conditions array.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
trait MockSelectQueryBuilderTrait {

	/**
	 * @param array $rows Rows for the builder to return on fetch* calls.
	 *   Each row is typically a stdClass or associative array.
	 * @param array &$whereConditions Captures where()/andWhere() arguments
	 *   for assertion. Subqueries share the same array.
	 * @param array &$capturedSelects Captures select() arguments for
	 *   assertion. When omitted, select() simply returns the builder.
	 *   Subqueries share the same array.
	 * @param array &$capturedTables Captures from()/table()/tables()/rawTables()
	 *   arguments in call order. Each invocation appends one entry. Lets tests
	 *   verify which table the chain was pointed at, including the multi-table
	 *   tables()/rawTables() form. Subqueries share the same array.
	 */
	private function createMockSelectQueryBuilder(
		array $rows = [],
		array &$whereConditions = [],
		array &$capturedSelects = [],
		array &$capturedTables = []
	): SelectQueryBuilder {
		$queryBuilder = $this->getMockBuilder( SelectQueryBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$chainMethods = [ 'fields', 'field',
			'join', 'leftJoin', 'straightJoin', 'joinConds',
			'groupBy', 'having', 'orderBy', 'caller', 'distinct',
			'limit', 'offset', 'options', 'option', 'conds',
			'useIndex', 'ignoreIndex', 'recency', 'clearFields',
			'lockInShareMode', 'forUpdate' ];

		foreach ( $chainMethods as $method ) {
			$queryBuilder->expects( $this->any() )
				->method( $method )
				->willReturnSelf();
		}

		$captureWhere = static function ( $conds ) use ( $queryBuilder, &$whereConditions ) {
			$whereConditions[] = $conds;
			return $queryBuilder;
		};

		$queryBuilder->expects( $this->any() )
			->method( 'where' )
			->willReturnCallback( $captureWhere );
		$queryBuilder->expects( $this->any() )
			->method( 'andWhere' )
			->willReturnCallback( $captureWhere );

		$queryBuilder->expects( $this->any() )
			->method( 'select' )
			->willReturnCallback( static function ( $fields ) use ( $queryBuilder, &$capturedSelects ) {
				$capturedSelects[] = $fields;
				return $queryBuilder;
			} );

		$captureTable = static function ( $table ) use ( $queryBuilder, &$capturedTables ) {
			$capturedTables[] = $table;
			return $queryBuilder;
		};

		foreach ( [ 'from', 'table', 'tables', 'rawTables' ] as $tableMethod ) {
			$queryBuilder->expects( $this->any() )
				->method( $tableMethod )
				->willReturnCallback( $captureTable );
		}

		$queryBuilder->expects( $this->any() )
			->method( 'newSubquery' )
			->willReturnCallback(
				fn () => $this->createMockSelectQueryBuilder(
					$rows, $whereConditions, $capturedSelects, $capturedTables
				)
			);

		$queryBuilder->expects( $this->any() )
			->method( 'fetchResultSet' )
			->willReturn( new FakeResultWrapper( $rows ) );

		$firstRow = $rows[0] ?? false;
		$queryBuilder->expects( $this->any() )
			->method( 'fetchRow' )
			->willReturn( $firstRow );

		$firstField = false;
		if ( is_object( $firstRow ) ) {
			$vars = get_object_vars( $firstRow );
			$firstField = $vars[array_key_first( $vars )] ?? false;
		} elseif ( is_array( $firstRow ) ) {
			$firstField = reset( $firstRow );
		}
		$queryBuilder->expects( $this->any() )
			->method( 'fetchField' )
			->willReturn( $firstField );

		return $queryBuilder;
	}

}
