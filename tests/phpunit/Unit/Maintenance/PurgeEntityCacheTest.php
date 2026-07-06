<?php

namespace SMW\Tests\Unit\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\EntityCache;
use SMW\Maintenance\purgeEntityCache;
use SMW\SQLStore\SQLStore;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use stdClass;
use Wikimedia\Rdbms\Database;

/**
 * @covers \SMW\Maintenance\purgeEntityCache
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class PurgeEntityCacheTest extends TestCase {

	use MockSelectQueryBuilderTrait;

	private $messageReporter;
	private $store;
	private $connection;
	private $entityCache;

	protected function setUp(): void {
		$this->messageReporter = $this->createMock( MessageReporter::class );
		$this->store = $this->createMock( SQLStore::class );
		$this->connection = $this->createMock( Database::class );
		$this->entityCache = $this->createMock( EntityCache::class );
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			purgeEntityCache::class,
			new purgeEntityCache()
		);
	}

	public function testExecute() {
		$row = new stdClass;
		$row->smw_id = 42;
		$row->smw_title = 'Foo';
		$row->smw_namespace = '0';
		$row->smw_iw = '';
		$row->smw_subobject = '';

		$subject = new WikiPage( 'Foo', 0 );

		$this->connection->method( 'addQuotes' )
			->willReturnArgument( 0 );

		$whereConditions = [];
		$this->connection->method( 'newSelectQueryBuilder' )
			->willReturnCallback(
				function () use ( $row, &$whereConditions ) {
					return $this->createMockSelectQueryBuilder( [ $row ], $whereConditions );
				}
			);

		$this->store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->entityCache->expects( $this->atLeastOnce() )
			->method( 'invalidate' )
			->with( $subject );

		$instance = new purgeEntityCache();

		$instance->setStore( $this->store );
		$instance->setEntityCache( $this->entityCache );

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$instance->execute();

		$this->assertSame(
			[
				[
					"smw_subobject=''",
					'smw_iw != ' . SMW_SQL3_SMWDELETEIW,
				],
			],
			$whereConditions
		);
	}

}
