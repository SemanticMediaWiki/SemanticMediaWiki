<?php

namespace SMW\Tests\Unit\SQLStore\TableBuilder\Examiner;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\Examiner\TouchedField;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;

/**
 * @covers \SMW\SQLStore\TableBuilder\Examiner\TouchedField
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class TouchedFieldTest extends TestCase {

	use MockSelectQueryBuilderTrait;
	use MockWriteQueryBuilderTrait;

	private $spyMessageReporter;
	private $store;

	protected function setUp(): void {
		parent::setUp();
		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TouchedField::class,
			new TouchedField( $this->store )
		);
	}

	public function testCheck() {
		$row = [
			'count' => 42
		];

		$selectBuilder = $this->createMockSelectQueryBuilder( [ (object)$row ] );

		$capturedTables = [];
		$capturedSets = [];
		$capturedWheres = [];
		$updateBuilder = $this->createMockUpdateQueryBuilder(
			$capturedTables,
			$capturedSets,
			$capturedWheres
		);

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'timestamp' )
			->willReturnArgument( 0 );

		$connection->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$connection->expects( $this->atLeastOnce() )
			->method( 'newUpdateQueryBuilder' )
			->willReturn( $updateBuilder );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new TouchedField(
			$this->store
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->check();

		$this->assertSame( [ SQLStore::ID_TABLE, SQLStore::ID_TABLE ], $capturedTables );

		$this->assertSame(
			[ [ 'smw_touched IS NULL' ], [ 'smw_iw' => SMW_SQL3_SMWBORDERIW ] ],
			$capturedWheres
		);

		$this->assertSame(
			[
				[ 'smw_touched' => '1970-01-01 00:00:00' ],
				[ 'smw_touched' => null ],
			],
			$capturedSets
		);

		$this->assertStringContainsString(
			'updating 42 rows with',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
