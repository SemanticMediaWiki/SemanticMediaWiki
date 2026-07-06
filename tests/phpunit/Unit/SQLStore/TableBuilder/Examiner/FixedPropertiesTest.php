<?php

namespace SMW\Tests\Unit\SQLStore\TableBuilder\Examiner;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\Examiner\FixedProperties;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;

/**
 * @covers \SMW\SQLStore\TableBuilder\Examiner\FixedProperties
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class FixedPropertiesTest extends TestCase {

	use MockSelectQueryBuilderTrait;
	use MockWriteQueryBuilderTrait;

	private $spyMessageReporter;
	private $store;
	private $connection;

	protected function setUp(): void {
		parent::setUp();
		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			FixedProperties::class,
			new FixedProperties( $this->store )
		);
	}

	public function testCheck() {
		$idTable = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$selectBuilderFoo = $this->createMockSelectQueryBuilder(
			[ (object)[ 'smw_id' => 99999 ] ]
		);
		$selectBuilderBar = $this->createMockSelectQueryBuilder(
			[ (object)[ 'smw_id' => 11111 ] ]
		);

		$capturedDeleteTables = [];
		$capturedDeleteWheres = [];
		$deleteBuilder = $this->createMockDeleteQueryBuilder(
			$capturedDeleteTables,
			$capturedDeleteWheres
		);

		$capturedUpdateTables = [];
		$capturedUpdateSets = [];
		$capturedUpdateWheres = [];
		$updateBuilder = $this->createMockUpdateQueryBuilder(
			$capturedUpdateTables,
			$capturedUpdateSets,
			$capturedUpdateWheres
		);

		$this->connection->expects( $this->atLeastOnce() )
			->method( 'newSelectQueryBuilder' )
			->willReturnOnConsecutiveCalls(
				$selectBuilderFoo,
				$selectBuilderBar
			);

		$this->connection->expects( $this->any() )
			->method( 'newDeleteQueryBuilder' )
			->willReturn( $deleteBuilder );

		$this->connection->expects( $this->any() )
			->method( 'newUpdateQueryBuilder' )
			->willReturn( $updateBuilder );

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $idTable );

		$instance = new FixedProperties(
			$this->store
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->setFixedProperties( [ '_FOO' => 51, '_BAR' => 52 ] );
		$instance->setProperties( [ '_FOO', '_BAR' ] );

		$instance->check();

		$this->assertSame(
			[ SQLStore::ID_TABLE, SQLStore::ID_TABLE ],
			$capturedDeleteTables
		);

		$this->assertSame(
			[ [ 'smw_id' => 51 ], [ 'smw_id' => 52 ] ],
			$capturedDeleteWheres
		);

		$this->assertSame(
			[
				SQLStore::QUERY_LINKS_TABLE,
				SQLStore::PROPERTY_STATISTICS_TABLE,
				SQLStore::QUERY_LINKS_TABLE,
				SQLStore::PROPERTY_STATISTICS_TABLE,
			],
			$capturedUpdateTables
		);

		$this->assertSame(
			[
				[ 'o_id' => 51 ],
				[ 'p_id' => 51 ],
				[ 'o_id' => 52 ],
				[ 'p_id' => 52 ],
			],
			$capturedUpdateSets
		);

		$this->assertSame(
			[
				[ 'o_id' => 99999 ],
				[ 'p_id' => 99999 ],
				[ 'o_id' => 11111 ],
				[ 'p_id' => 11111 ],
			],
			$capturedUpdateWheres
		);

		$expected = $this->spyMessageReporter->getMessagesAsString();

		$this->assertStringContainsString(
			'Checking selected fixed properties IDs',
			$expected
		);

		$this->assertStringContainsString(
			'moving from 99999 to 51',
			$expected
		);

		$this->assertStringContainsString(
			'moving from 11111 to 52',
			$expected
		);
	}

}
