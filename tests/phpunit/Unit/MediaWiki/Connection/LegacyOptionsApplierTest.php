<?php

namespace SMW\Tests\Unit\MediaWiki\Connection;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\LegacyOptionsApplier;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @covers \SMW\MediaWiki\Connection\LegacyOptionsApplier
 *
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class LegacyOptionsApplierTest extends TestCase {

	public function testEmptyOptionsAppliesNothing(): void {
		$queryBuilder = $this->createMock( SelectQueryBuilder::class );

		$queryBuilder->expects( $this->never() )->method( 'limit' );
		$queryBuilder->expects( $this->never() )->method( 'offset' );
		$queryBuilder->expects( $this->never() )->method( 'orderBy' );
		$queryBuilder->expects( $this->never() )->method( 'groupBy' );
		$queryBuilder->expects( $this->never() )->method( 'having' );
		$queryBuilder->expects( $this->never() )->method( 'distinct' );

		LegacyOptionsApplier::applyTo( $queryBuilder, [] );
	}

	public function testAppliesAllRecognisedOptions(): void {
		$queryBuilder = $this->createMock( SelectQueryBuilder::class );

		$queryBuilder->expects( $this->once() )->method( 'limit' )->with( 50 )->willReturnSelf();
		$queryBuilder->expects( $this->once() )->method( 'offset' )->with( 10 )->willReturnSelf();
		$queryBuilder->expects( $this->once() )->method( 'orderBy' )->with( 'smw_sort' )->willReturnSelf();
		$queryBuilder->expects( $this->once() )->method( 'groupBy' )->with( 'smw_id' )->willReturnSelf();
		$queryBuilder->expects( $this->once() )->method( 'having' )->with( 'COUNT(*) > 1' )->willReturnSelf();
		$queryBuilder->expects( $this->once() )->method( 'distinct' )->willReturnSelf();

		LegacyOptionsApplier::applyTo( $queryBuilder, [
			'LIMIT' => 50,
			'OFFSET' => 10,
			'ORDER BY' => 'smw_sort',
			'GROUP BY' => 'smw_id',
			'HAVING' => 'COUNT(*) > 1',
			'DISTINCT' => true,
		] );
	}

	public function testRecognisesNumericKeyedDistinct(): void {
		$queryBuilder = $this->createMock( SelectQueryBuilder::class );

		$queryBuilder->expects( $this->once() )->method( 'distinct' )->willReturnSelf();

		LegacyOptionsApplier::applyTo( $queryBuilder, [ 'DISTINCT' ] );
	}

	public function testIgnoresUnknownKeys(): void {
		$queryBuilder = $this->createMock( SelectQueryBuilder::class );

		$queryBuilder->expects( $this->never() )->method( 'limit' );

		// No method is invoked: arbitrary keys do not match any branch.
		LegacyOptionsApplier::applyTo( $queryBuilder, [
			'STRAIGHT_JOIN' => true,
			'FOR UPDATE' => true,
		] );

		// Explicit assertion so the test is not flagged risky in strict mode.
		$this->addToAssertionCount( 1 );
	}
}
