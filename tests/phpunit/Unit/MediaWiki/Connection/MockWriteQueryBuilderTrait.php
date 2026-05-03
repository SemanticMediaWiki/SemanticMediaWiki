<?php

namespace SMW\Tests\Unit\MediaWiki\Connection;

use Wikimedia\Rdbms\DeleteQueryBuilder;
use Wikimedia\Rdbms\InsertQueryBuilder;
use Wikimedia\Rdbms\ReplaceQueryBuilder;
use Wikimedia\Rdbms\UpdateQueryBuilder;

/**
 * Test helper for mocking the write-side QueryBuilder chains
 * (`InsertQueryBuilder`, `UpdateQueryBuilder`, `DeleteQueryBuilder`,
 * `ReplaceQueryBuilder`). Each helper returns a chainable mock that
 * captures the most relevant arguments into shared arrays so individual
 * tests can assert on the row data, set clause, where conditions etc.
 *
 * `execute()` is stubbed (does nothing). Tests still need to wire the
 * mock into the `Database` mock via `expects()->method('new*QueryBuilder')
 * ->willReturn( $mock )`.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
trait MockWriteQueryBuilderTrait {

	/**
	 * @param array &$tables Captures `insertInto()`/`insert()`/`table()`
	 *   arguments in call order. Successive calls append.
	 * @param array &$rows Captures `row()` and `rows()` arguments in call
	 *   order. `row($a)` followed by `rows([$b, $c])` yields
	 *   `[$a, [$b, $c]]`.
	 * @param array &$sets Captures `set()`/`andSet()` arguments in call
	 *   order. Successive calls append.
	 * @param array &$uniqueIndexFields Captures `uniqueIndexFields()`
	 *   arguments in call order. Successive calls append.
	 */
	private function createMockInsertQueryBuilder(
		array &$tables = [],
		array &$rows = [],
		array &$sets = [],
		array &$uniqueIndexFields = []
	): InsertQueryBuilder {
		$builder = $this->getMockBuilder( InsertQueryBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$captureTable = static function ( $table ) use ( $builder, &$tables ) {
			$tables[] = $table;
			return $builder;
		};
		$builder->method( 'insertInto' )->willReturnCallback( $captureTable );
		$builder->method( 'insert' )->willReturnCallback( $captureTable );
		$builder->method( 'table' )->willReturnCallback( $captureTable );

		$captureRow = static function ( $row ) use ( $builder, &$rows ) {
			$rows[] = $row;
			return $builder;
		};
		$builder->method( 'row' )->willReturnCallback( $captureRow );
		$builder->method( 'rows' )->willReturnCallback( $captureRow );

		$captureSet = static function ( $set ) use ( $builder, &$sets ) {
			$sets[] = $set;
			return $builder;
		};
		$builder->method( 'set' )->willReturnCallback( $captureSet );
		$builder->method( 'andSet' )->willReturnCallback( $captureSet );

		$captureUnique = static function ( $fields ) use ( $builder, &$uniqueIndexFields ) {
			$uniqueIndexFields[] = $fields;
			return $builder;
		};
		$builder->method( 'uniqueIndexFields' )->willReturnCallback( $captureUnique );

		foreach ( [ 'onDuplicateKeyUpdate', 'ignore', 'caller', 'option', 'options' ] as $passthrough ) {
			$builder->method( $passthrough )->willReturn( $builder );
		}

		$builder->method( 'execute' );

		return $builder;
	}

	/**
	 * @param array &$tables Captures `update()`/`table()` arguments in call
	 *   order. Successive calls append.
	 * @param array &$sets Captures `set()`/`andSet()` arguments in call
	 *   order. Successive calls append.
	 * @param array &$wheres Captures `where()`/`andWhere()`/`conds()`
	 *   arguments in call order. Successive calls append.
	 */
	private function createMockUpdateQueryBuilder(
		array &$tables = [],
		array &$sets = [],
		array &$wheres = []
	): UpdateQueryBuilder {
		$builder = $this->getMockBuilder( UpdateQueryBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$captureTable = static function ( $table ) use ( $builder, &$tables ) {
			$tables[] = $table;
			return $builder;
		};
		$builder->method( 'update' )->willReturnCallback( $captureTable );
		$builder->method( 'table' )->willReturnCallback( $captureTable );

		$captureSet = static function ( $set ) use ( $builder, &$sets ) {
			$sets[] = $set;
			return $builder;
		};
		$builder->method( 'set' )->willReturnCallback( $captureSet );
		$builder->method( 'andSet' )->willReturnCallback( $captureSet );

		$captureWhere = static function ( $cond ) use ( $builder, &$wheres ) {
			$wheres[] = $cond;
			return $builder;
		};
		$builder->method( 'where' )->willReturnCallback( $captureWhere );
		$builder->method( 'andWhere' )->willReturnCallback( $captureWhere );
		$builder->method( 'conds' )->willReturnCallback( $captureWhere );

		foreach ( [ 'ignore', 'caller', 'option', 'options' ] as $passthrough ) {
			$builder->method( $passthrough )->willReturn( $builder );
		}

		$builder->method( 'execute' );

		return $builder;
	}

	/**
	 * @param array &$tables Captures `deleteFrom()`/`delete()`/`table()`
	 *   arguments in call order. Successive calls append.
	 * @param array &$wheres Captures `where()`/`andWhere()`/`conds()`
	 *   arguments in call order. Successive calls append.
	 */
	private function createMockDeleteQueryBuilder(
		array &$tables = [],
		array &$wheres = []
	): DeleteQueryBuilder {
		$builder = $this->getMockBuilder( DeleteQueryBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$captureTable = static function ( $table ) use ( $builder, &$tables ) {
			$tables[] = $table;
			return $builder;
		};
		$builder->method( 'deleteFrom' )->willReturnCallback( $captureTable );
		$builder->method( 'delete' )->willReturnCallback( $captureTable );
		$builder->method( 'table' )->willReturnCallback( $captureTable );

		$captureWhere = static function ( $cond ) use ( $builder, &$wheres ) {
			$wheres[] = $cond;
			return $builder;
		};
		$builder->method( 'where' )->willReturnCallback( $captureWhere );
		$builder->method( 'andWhere' )->willReturnCallback( $captureWhere );
		$builder->method( 'conds' )->willReturnCallback( $captureWhere );

		$builder->method( 'caller' )->willReturn( $builder );
		$builder->method( 'execute' );

		return $builder;
	}

	/**
	 * @param array &$tables Captures `replaceInto()`/`table()` arguments in
	 *   call order. Successive calls append.
	 * @param array &$rows Captures `row()` and `rows()` arguments in call
	 *   order. `row($a)` followed by `rows([$b, $c])` yields
	 *   `[$a, [$b, $c]]`.
	 * @param array &$uniqueIndexFields Captures `uniqueIndexFields()`
	 *   arguments in call order. Successive calls append.
	 */
	private function createMockReplaceQueryBuilder(
		array &$tables = [],
		array &$rows = [],
		array &$uniqueIndexFields = []
	): ReplaceQueryBuilder {
		$builder = $this->getMockBuilder( ReplaceQueryBuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$captureTable = static function ( $table ) use ( $builder, &$tables ) {
			$tables[] = $table;
			return $builder;
		};
		$builder->method( 'replaceInto' )->willReturnCallback( $captureTable );
		$builder->method( 'table' )->willReturnCallback( $captureTable );

		$captureRow = static function ( $row ) use ( $builder, &$rows ) {
			$rows[] = $row;
			return $builder;
		};
		$builder->method( 'row' )->willReturnCallback( $captureRow );
		$builder->method( 'rows' )->willReturnCallback( $captureRow );

		$captureUnique = static function ( $fields ) use ( $builder, &$uniqueIndexFields ) {
			$uniqueIndexFields[] = $fields;
			return $builder;
		};
		$builder->method( 'uniqueIndexFields' )->willReturnCallback( $captureUnique );

		$builder->method( 'caller' )->willReturn( $builder );
		$builder->method( 'execute' );

		return $builder;
	}
}
