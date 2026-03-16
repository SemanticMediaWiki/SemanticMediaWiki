<?php

namespace SMW\Tests\Elastic\Hooks;

use PHPUnit\Framework\TestCase;
use SMW\DIWikiPage;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\Hooks\UpdateEntityCollationComplete;
use SMW\Elastic\Indexer\Rebuilder\Rebuilder;
use SMW\MediaWiki\Connection\Database;
use SMW\SemanticData;
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
			->disableOriginalConstructor()
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

		$this->testEnvironment->registerObject( 'Store', $this->store );
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
		$dataItem = $this->getMockBuilder( DIWikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$row = [
			'smw_id'  => 42,
			'smw_iw'  => '',
			'smw_rev' => 9999
		];

		$this->rebuilder->expects( $this->any() )
			->method( 'ping' )
			->willReturn( true );

		$this->rebuilder->expects( $this->any() )
			->method( 'select' )
			->willReturn( [ new FakeResultWrapper( [ (object)$row ] ), 2 ] );

		$this->entityIdManager->expects( $this->any() )
			->method( 'getDataItemById' )
			->willReturn( $dataItem );

		$instance = new UpdateEntityCollationComplete(
			$this->store,
			$this->messageReporter
		);

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$instance->setCountDown( 0 );
		$instance->runUpdate( $this->rebuilder );

		$this->assertStringContainsString(
			'... updating document ...                                   1 / 1 (100%)',
			$this->messageReporter->getMessagesAsString()
		);
	}

}
