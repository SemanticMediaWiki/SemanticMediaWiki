<?php

namespace SMW\Tests\Unit\SQLStore;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\KeysetPredicateBuilder;

/**
 * @covers \SMW\SQLStore\KeysetPredicateBuilder
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class KeysetPredicateBuilderTest extends TestCase {

	private $connection;

	protected function setUp(): void {
		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->method( 'addQuotes' )
			->willReturnCallback( static fn ( $v ) => "'$v'" );
	}

	public function testSingleLevelAscending() {
		$sql = KeysetPredicateBuilder::build(
			$this->connection,
			[ [ 'column' => 'smw_sort', 'value' => 'Alpha', 'order' => 'ASC' ] ],
			'smw_id',
			42,
			'ASC'
		);

		$this->assertSame(
			"(smw_sort > 'Alpha') OR (smw_sort = 'Alpha' AND smw_id > 42)",
			$sql
		);
	}

	public function testSingleLevelDescending() {
		$sql = KeysetPredicateBuilder::build(
			$this->connection,
			[ [ 'column' => 'smw_sort', 'value' => 'Alpha', 'order' => 'DESC' ] ],
			'smw_id',
			42,
			'DESC'
		);

		$this->assertSame(
			"(smw_sort < 'Alpha') OR (smw_sort = 'Alpha' AND smw_id < 42)",
			$sql
		);
	}

	public function testMultiLevelUniformAscending() {
		$sql = KeysetPredicateBuilder::build(
			$this->connection,
			[
				[ 'column' => 't1.o_sortkey', 'value' => 'a', 'order' => 'ASC' ],
				[ 'column' => 't2.o_sortkey', 'value' => 'b', 'order' => 'ASC' ],
			],
			't0.smw_id',
			9,
			'ASC'
		);

		$this->assertSame(
			"(t1.o_sortkey > 'a')"
				. " OR (t1.o_sortkey = 'a' AND t2.o_sortkey > 'b')"
				. " OR (t1.o_sortkey = 'a' AND t2.o_sortkey = 'b' AND t0.smw_id > 9)",
			$sql
		);
	}

	public function testMultiLevelMixedDirections() {
		// Phase 3b-iii shape: level 0 ASC, level 1 DESC, tiebreak follows
		// the last level (DESC).
		$sql = KeysetPredicateBuilder::build(
			$this->connection,
			[
				[ 'column' => 't1.o_sortkey', 'value' => 'a', 'order' => 'ASC' ],
				[ 'column' => 't2.o_sortkey', 'value' => 'b', 'order' => 'DESC' ],
			],
			't0.smw_id',
			9,
			'DESC'
		);

		$this->assertSame(
			"(t1.o_sortkey > 'a')"
				. " OR (t1.o_sortkey = 'a' AND t2.o_sortkey < 'b')"
				. " OR (t1.o_sortkey = 'a' AND t2.o_sortkey = 'b' AND t0.smw_id < 9)",
			$sql
		);
	}

	public function testNonDescOrderValueIsTreatedAsAscending() {
		$sql = KeysetPredicateBuilder::build(
			$this->connection,
			[ [ 'column' => 'smw_sort', 'value' => 'Alpha', 'order' => 'asc' ] ],
			'smw_id',
			1,
			'whatever'
		);

		$this->assertSame(
			"(smw_sort > 'Alpha') OR (smw_sort = 'Alpha' AND smw_id > 1)",
			$sql
		);
	}

	public function testEmptyLevelsThrows() {
		$this->expectException( InvalidArgumentException::class );

		KeysetPredicateBuilder::build( $this->connection, [], 'smw_id', 1, 'ASC' );
	}

}
