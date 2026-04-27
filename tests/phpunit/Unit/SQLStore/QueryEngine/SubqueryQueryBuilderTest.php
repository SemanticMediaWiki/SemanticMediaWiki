<?php

namespace SMW\Tests\Unit\SQLStore\QueryEngine;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMW\SQLStore\QueryEngine\SubqueryQueryBuilder;

/**
 * @covers \SMW\SQLStore\QueryEngine\SubqueryQueryBuilder
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class SubqueryQueryBuilderTest extends TestCase {

	private $connection;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->method( 'tableName' )
			->willReturnCallback( static fn ( string $t ) => "`$t`" );
	}

	public function testInstanceQueryWithSimpleSortByPropertyValue() {
		$root = new QuerySegment();
		$root->joinTable = 'smw_fpt_cdat';
		$root->alias = 't0';
		$root->joinfield = 't0.s_id';
		$root->where = "t0.o_sortkey>'2456704.5'";
		$root->sortfields = [ '_CDAT' => 't0.o_sortkey' ];

		$sqlOptions = [
			'LIMIT' => 55,
			'OFFSET' => 0,
			'ORDER BY' => 't0.o_sortkey ASC',
		];

		$outerWhere = "outer_q.smw_iw!=':smw' AND outer_q.smw_iw!=':smw-delete' AND outer_q.smw_iw!=':smw-redi'";

		$builder = new SubqueryQueryBuilder( $this->connection );
		$sql = $builder->buildInstanceQuerySQL( $root, $sqlOptions, $outerWhere );

		$this->assertStringContainsString( 'FROM `smw_object_ids` AS outer_q', $sql );
		$this->assertStringContainsString( 'INNER JOIN (', $sql );
		$this->assertStringContainsString( 'SELECT DISTINCT', $sql );
		$this->assertStringContainsString( "outer_q.smw_iw!=':smw'", $sql );
		// Inner LIMIT: max(55+5, ceil(55*1.2)+10) = max(60, 76) = 76
		$this->assertStringContainsString( 'LIMIT 76', $sql );
		// Outer LIMIT
		$this->assertMatchesRegularExpression( '/LIMIT 55\s*$/', $sql );
		// Outer ORDER BY references the inner alias
		$this->assertMatchesRegularExpression( '/ORDER BY inner_q\.\w+ ASC/', $sql );
	}

	public function testInstanceQueryWithoutSort() {
		$root = new QuerySegment();
		$root->joinTable = 'smw_fpt_askdu';
		$root->alias = 't0';
		$root->joinfield = 't0.s_id';
		$root->where = '';
		$root->sortfields = [];

		$sqlOptions = [ 'LIMIT' => 100, 'OFFSET' => 0, 'ORDER BY' => '' ];
		$outerWhere = "outer_q.smw_iw!=':smw'";

		$builder = new SubqueryQueryBuilder( $this->connection );
		$sql = $builder->buildInstanceQuerySQL( $root, $sqlOptions, $outerWhere );

		$this->assertStringContainsString( 'SELECT DISTINCT t0.s_id', $sql );
		// No outer ORDER BY when none requested
		$this->assertDoesNotMatchRegularExpression( '/ORDER BY/', $sql );
		// Inner LIMIT: max(100+5, ceil(100*1.2)+10) = max(105, 130) = 130
		$this->assertStringContainsString( 'LIMIT 130', $sql );
		$this->assertMatchesRegularExpression( '/LIMIT 100\s*$/', $sql );
	}

	public function testInstanceQueryWithFromClause() {
		$root = new QuerySegment();
		$root->joinTable = 'smw_di_wikipage';
		$root->alias = 't0';
		$root->joinfield = 't0.s_id';
		$root->where = 't0.p_id=42';
		$root->from = ' INNER JOIN `smw_object_ids` AS idst0 ON idst0.smw_id=t0.o_id';
		$root->sortfields = [ '_SKEY' => 'idst0.smw_sort' ];

		$sqlOptions = [
			'LIMIT' => 50,
			'OFFSET' => 0,
			'ORDER BY' => 'idst0.smw_sort ASC',
		];

		$builder = new SubqueryQueryBuilder( $this->connection );
		$sql = $builder->buildInstanceQuerySQL( $root, $sqlOptions, '' );

		// The nested join is INSIDE the derived table
		$this->assertMatchesRegularExpression(
			'/INNER JOIN \(.*INNER JOIN `smw_object_ids` AS idst0.*\) AS inner_q/s',
			$sql
		);
	}

	public function testCountQuery() {
		$root = new QuerySegment();
		$root->joinTable = 'smw_fpt_cdat';
		$root->alias = 't0';
		$root->joinfield = 't0.s_id';
		$root->where = "t0.o_sortkey>'2456704.5'";

		$sqlOptions = [ 'LIMIT' => 51, 'OFFSET' => 0, 'ORDER BY' => '' ];
		$outerWhere = "outer_q.smw_iw!=':smw'";

		$builder = new SubqueryQueryBuilder( $this->connection );
		$sql = $builder->buildCountQuerySQL( $root, $sqlOptions, $outerWhere );

		$this->assertStringContainsString( 'COUNT(*)', $sql );
		$this->assertStringContainsString( 'SELECT DISTINCT t0.s_id', $sql );
		$this->assertStringContainsString( "outer_q.smw_iw!=':smw'", $sql );
		$this->assertStringNotContainsString( 'ORDER BY', $sql );
		$this->assertStringNotContainsString( 'LIMIT', $sql );
	}

	public function testFiltersOnlyAtOuterLevel() {
		$root = new QuerySegment();
		$root->joinTable = 'smw_fpt_cdat';
		$root->alias = 't0';
		$root->joinfield = 't0.s_id';
		$root->where = "t0.o_sortkey>'X'";
		$root->sortfields = [ '_CDAT' => 't0.o_sortkey' ];

		$sqlOptions = [ 'LIMIT' => 10, 'OFFSET' => 0, 'ORDER BY' => 't0.o_sortkey ASC' ];
		$outerWhere = "outer_q.smw_iw!=':smw'";

		$builder = new SubqueryQueryBuilder( $this->connection );
		$sql = $builder->buildInstanceQuerySQL( $root, $sqlOptions, $outerWhere );

		// Outer filter must be outside the derived table. Note that the outer
		// projection legitimately references outer_q.smw_iw, so we assert on
		// the filter literal value (':smw') rather than the column name.
		[ $beforeJoin, $afterJoin ] = explode( ') AS inner_q', $sql, 2 );
		$this->assertStringNotContainsString( "':smw'", $beforeJoin );
		$this->assertStringContainsString( "outer_q.smw_iw!=':smw'", $afterJoin );
	}

	public function testInstanceQueryWithCompoundSortfield() {
		// Mirrors OrderCondition.php:161 — sort label '#' produces a
		// comma-separated value, then QueryEngine::getSQLOptions splices
		// the direction into the middle.
		$root = new QuerySegment();
		$root->joinTable = 'smw_object_ids';
		$root->alias = 't0';
		$root->joinfield = 't0.smw_id';
		$root->where = '';
		$root->sortfields = [ '#' => 't0.smw_sort,t0.smw_title,t0.smw_subobject' ];

		$sqlOptions = [
			'LIMIT' => 25,
			'OFFSET' => 0,
			'ORDER BY' => 't0.smw_sort ASC,t0.smw_title ASC,t0.smw_subobject ASC ',
		];

		$builder = new SubqueryQueryBuilder( $this->connection );
		$sql = $builder->buildInstanceQuerySQL( $root, $sqlOptions, '' );

		// Each compound piece gets its own alias inside the derived table
		$this->assertStringContainsString( 't0.smw_sort AS sf0', $sql );
		$this->assertStringContainsString( 't0.smw_title AS sf1', $sql );
		$this->assertStringContainsString( 't0.smw_subobject AS sf2', $sql );

		// Outer ORDER BY references the inner aliases — each piece rewritten
		$this->assertStringContainsString( 'inner_q.sf0 ASC', $sql );
		$this->assertStringContainsString( 'inner_q.sf1 ASC', $sql );
		$this->assertStringContainsString( 'inner_q.sf2 ASC', $sql );

		// No raw t0 references survive in the outer ORDER BY
		[ , $afterJoin ] = explode( ') AS inner_q', $sql, 2 );
		$orderBySection = strstr( $afterJoin, 'ORDER BY' );
		$this->assertStringNotContainsString( 't0.smw_sort', $orderBySection );
		$this->assertStringNotContainsString( 't0.smw_title', $orderBySection );
		$this->assertStringNotContainsString( 't0.smw_subobject', $orderBySection );
	}

	public function testRewriteOrderByHandlesPrefixCollisions() {
		// Two sortfield expressions where one is a prefix of another.
		$root = new QuerySegment();
		$root->joinTable = 'smw_di_wikipage';
		$root->alias = 't0';
		$root->joinfield = 't0.s_id';
		$root->where = '';
		$root->sortfields = [
			'_PROP_A' => 't0.smw_sort',
			'_PROP_B' => 't0.smw_sortkey',
		];

		$sqlOptions = [
			'LIMIT' => 10,
			'OFFSET' => 0,
			'ORDER BY' => 't0.smw_sort ASC, t0.smw_sortkey DESC',
		];

		$builder = new SubqueryQueryBuilder( $this->connection );
		$sql = $builder->buildInstanceQuerySQL( $root, $sqlOptions, '' );

		[ , $afterJoin ] = explode( ') AS inner_q', $sql, 2 );
		$orderBy = strstr( $afterJoin, 'ORDER BY' );

		// Each sortfield must be rewritten to its own distinct alias.
		// Without length-sorted substitution, the shorter expression
		// "t0.smw_sort" would partially replace the longer "t0.smw_sortkey",
		// producing "inner_q.sf0key DESC" instead of "inner_q.sf1 DESC".
		$this->assertStringContainsString( 'inner_q.sf0 ASC', $orderBy );
		$this->assertStringContainsString( 'inner_q.sf1 DESC', $orderBy );
		$this->assertStringNotContainsString( 'sf0key', $orderBy );
	}

	public function testInstanceQueryWithIdAnchoredRoot() {
		// Mirrors what ConditionBuilder produces post-process: root is the
		// ID table, property table is joined via $root->from.
		$root = new QuerySegment();
		$root->joinTable = 'smw_object_ids';
		$root->alias = 't0';
		$root->joinfield = 't0.smw_id';
		$root->from = ' INNER JOIN `smw_fpt_cdat` AS t1 ON t0.smw_id=t1.s_id';
		$root->where = "t1.o_sortkey>'2456704.5'";
		$root->sortfields = [ '_CDAT' => 't1.o_sortkey' ];

		$sqlOptions = [
			'LIMIT' => 30,
			'OFFSET' => 0,
			'ORDER BY' => 't1.o_sortkey ASC',
		];

		$builder = new SubqueryQueryBuilder( $this->connection );
		$sql = $builder->buildInstanceQuerySQL( $root, $sqlOptions, '' );

		// Inner SELECT projects t0.smw_id (the joinfield), aliased as s_id
		// so the outer JOIN's inner_q.s_id reference resolves.
		$this->assertStringContainsString( 't0.smw_id AS s_id', $sql );
		// No invalid t0.s_id reference (smw_object_ids has no s_id column)
		$this->assertStringNotContainsString( 't0.s_id', $sql );
		// Property-table join lives inside the derived table
		$this->assertMatchesRegularExpression(
			'/INNER JOIN \(.*INNER JOIN `smw_fpt_cdat` AS t1.*\) AS inner_q/s',
			$sql
		);
		// Outer JOIN uses the s_id alias the inner query exposes
		$this->assertStringContainsString( 'outer_q.smw_id = inner_q.s_id', $sql );
	}

	public function testCountQueryWithIdAnchoredRoot() {
		$root = new QuerySegment();
		$root->joinTable = 'smw_object_ids';
		$root->alias = 't0';
		$root->joinfield = 't0.smw_id';
		$root->from = ' INNER JOIN `smw_fpt_cdat` AS t1 ON t0.smw_id=t1.s_id';
		$root->where = "t1.o_sortkey>'X'";

		$sqlOptions = [ 'LIMIT' => 51, 'OFFSET' => 0, 'ORDER BY' => '' ];

		$builder = new SubqueryQueryBuilder( $this->connection );
		$sql = $builder->buildCountQuerySQL( $root, $sqlOptions, '' );

		// Inner SELECT projects the joinfield aliased as s_id
		$this->assertStringContainsString( 't0.smw_id AS s_id', $sql );
		$this->assertStringContainsString( 'COUNT(*)', $sql );
		// No invalid t0.s_id reference
		$this->assertStringNotContainsString( 't0.s_id', $sql );
	}
}
