<?php

namespace SMW\Tests\Unit\MediaWiki\Connection;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\MutedDeleteQueryBuilder;
use SMW\MediaWiki\Connection\MutedInsertQueryBuilder;
use SMW\MediaWiki\Connection\MutedReplaceQueryBuilder;
use SMW\MediaWiki\Connection\MutedUpdateQueryBuilder;
use SMW\MediaWiki\Connection\TransactionHandler;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\ScopedCallback;

/**
 * @covers \SMW\MediaWiki\Connection\MutedInsertQueryBuilder
 * @covers \SMW\MediaWiki\Connection\MutedUpdateQueryBuilder
 * @covers \SMW\MediaWiki\Connection\MutedDeleteQueryBuilder
 * @covers \SMW\MediaWiki\Connection\MutedReplaceQueryBuilder
 *
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class MutedQueryBuilderTest extends TestCase {

	/**
	 * @dataProvider builderProvider
	 */
	public function testExecuteMutesProfilerAroundParentExecute( string $builderClass, string $writeMethod, callable $configure ): void {
		$consumed = false;

		$transactionHandler = $this->createMock( TransactionHandler::class );
		$transactionHandler->expects( $this->once() )
			->method( 'muteTransactionProfiler' )
			->willReturnCallback( static function () use ( &$consumed ): ScopedCallback {
				return new ScopedCallback( static function () use ( &$consumed ): void {
					$consumed = true;
				} );
			} );

		$database = $this->createMock( IDatabase::class );
		$database->expects( $this->once() )
			->method( $writeMethod );

		$builder = new $builderClass( $database, $transactionHandler );
		$configure( $builder );
		$builder->execute();

		$this->assertTrue( $consumed, 'ScopedCallback was not consumed after execute()' );
	}

	/**
	 * @dataProvider builderProvider
	 */
	public function testExecuteHandlesNullScopeFromMute( string $builderClass, string $writeMethod, callable $configure ): void {
		$transactionHandler = $this->createMock( TransactionHandler::class );
		$transactionHandler->expects( $this->once() )
			->method( 'muteTransactionProfiler' )
			->willReturn( null );

		$database = $this->createMock( IDatabase::class );
		$database->expects( $this->once() )
			->method( $writeMethod );

		$builder = new $builderClass( $database, $transactionHandler );
		$configure( $builder );
		$builder->execute();

		// Mock expectations above verify the mute and write calls; record
		// an assertion so the test is not flagged as risky under strict mode.
		$this->addToAssertionCount( 1 );
	}

	public static function builderProvider(): array {
		return [
			'insert' => [
				MutedInsertQueryBuilder::class,
				'insert',
				static function ( MutedInsertQueryBuilder $b ): void {
					$b->insertInto( 'smw_test' )->row( [ 'a' => 1 ] )->caller( 'MutedQueryBuilderTest' );
				},
			],
			'update' => [
				MutedUpdateQueryBuilder::class,
				'update',
				static function ( MutedUpdateQueryBuilder $b ): void {
					$b->update( 'smw_test' )->set( [ 'a' => 1 ] )->where( [ 'id' => 1 ] )->caller( 'MutedQueryBuilderTest' );
				},
			],
			'delete' => [
				MutedDeleteQueryBuilder::class,
				'delete',
				static function ( MutedDeleteQueryBuilder $b ): void {
					$b->deleteFrom( 'smw_test' )->where( [ 'id' => 1 ] )->caller( 'MutedQueryBuilderTest' );
				},
			],
			'replace' => [
				MutedReplaceQueryBuilder::class,
				'replace',
				static function ( MutedReplaceQueryBuilder $b ): void {
					$b->replaceInto( 'smw_test' )->uniqueIndexFields( [ 'id' ] )->row( [ 'id' => 1 ] )->caller( 'MutedQueryBuilderTest' );
				},
			],
		];
	}
}
