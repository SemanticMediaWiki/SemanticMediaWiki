<?php

namespace SMW\Tests\Unit\Elastic\Hooks;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\Hooks\UpdateEntityCollationComplete;
use SMW\Elastic\Indexer\Rebuilder\Rebuilder;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\EntityIdManager;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;
use Wikimedia\Rdbms\FakeResultWrapper;

/**
 * @covers \SMW\Elastic\Hooks\UpdateEntityCollationComplete
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class UpdateEntityCollationCompleteTest extends TestCase {

	private TestEnvironment $testEnvironment;
	private $store;
	private $messageReporter;
	private $rebuilder;
	private $entityIdManager;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$this->messageReporter = $this->testEnvironment->getUtilityFactory()->newSpyMessageReporter();

		$this->entityIdManager = $this->getMockBuilder( EntityIdManager::class )
			->disableOriginalConstructor()
			->getMock();

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $this->entityIdManager );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->willReturn( $semanticData );

		$this->rebuilder = $this->getMockBuilder( Rebuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Client::class )
			->disableOriginalConstructor()
			->getMock();

		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$callback = static function ( $type ) use ( $connection, $database ) {
			if ( $type === 'mw.db' ) {
				return $database;
			}

			return $connection;
		};

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturnCallback( $callback );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceof(
			UpdateEntityCollationComplete::class,
			new UpdateEntityCollationComplete( $this->store )
		);
	}

	public function testRunUpdate() {
		$this->runUpdateOverRows( [ 42 ] );

		$this->assertStringContainsString(
			'... updating document ...                                   1 / 1 (100%)',
			$this->messageReporter->getMessagesAsString()
		);
	}

	public function testRunUpdateSendsTheDocumentsStillPendingAfterTheLastEntity() {
		$this->rebuilder->method( 'hasPendingDocuments' )
			->willReturn( true );

		$calls = [];
		$this->rebuilder->method( 'rebuild' )
			->willReturnCallback( static function () use ( &$calls ) {
				$calls[] = 'rebuild';
			} );
		$this->rebuilder->method( 'flush' )
			->willReturnCallback( static function () use ( &$calls ) {
				$calls[] = 'flush';
			} );

		$this->runUpdateOverRows( [ 42, 43 ] );

		$this->assertSame( [ 'rebuild', 'rebuild', 'flush' ], $calls );
	}

	/**
	 * @param int[] $ids Entity IDs of the selected rows
	 */
	private function runUpdateOverRows( array $ids ): void {
		$this->rebuilder->method( 'ping' )
			->willReturn( true );

		$rows = array_map( static fn ( int $id ) => (object)[ 'smw_id' => $id, 'smw_iw' => '', 'smw_rev' => 9999 ], $ids );
		$this->rebuilder->method( 'select' )
			->willReturn( [ new FakeResultWrapper( $rows ), max( $ids ) ] );

		$this->entityIdManager->method( 'getDataItemById' )
			->willReturn( WikiPage::newFromText( 'Foo' ) );

		$instance = new UpdateEntityCollationComplete( $this->store, $this->messageReporter );
		$instance->setMessageReporter( $this->messageReporter );
		$instance->setCountDown( 0 );
		$instance->runUpdate( $this->rebuilder );
	}

}
