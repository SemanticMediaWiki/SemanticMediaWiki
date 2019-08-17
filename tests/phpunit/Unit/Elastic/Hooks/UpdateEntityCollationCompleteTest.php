<?php

namespace SMW\Tests\Elastic\Hooks;

use SMW\Elastic\Hooks\UpdateEntityCollationComplete;
use SMW\Tests\PHPUnitCompat;
use SMW\Tests\TestEnvironment;
use FakeResultWrapper;

/**
 * @covers \SMW\Elastic\Config
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class UpdateEntityCollationCompleteTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $store;
	private $messageReporter;
	private $rebuilder;
	private $entityIdManager;

	protected function setUp() {

		$this->testEnvironment = new TestEnvironment();

		$this->messageReporter = $this->testEnvironment->getUtilityFactory()->newSpyMessageReporter();

		$this->entityIdManager = $this->getMockBuilder( '\SMW\SQLStore\EntityStore\EntityIdManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $this->entityIdManager ) );

		$this->store->expects( $this->any() )
			->method( 'getSemanticData' )
			->will( $this->returnValue( $this->semanticData ) );

		$this->rebuilder = $this->getMockBuilder( '\SMW\Elastic\Indexer\Rebuilder' )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();

		$this->database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$callback = function( $type ) {

			if ( $type === 'mw.db' ) {
				return $this->database;
			};

			return $this->connection;
		};

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnCallback( $callback ) );

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceof(
			UpdateEntityCollationComplete::class,
			new UpdateEntityCollationComplete( $this->store, $this->messageReporter )
		);
	}

	public function testRunUpdate() {

		$dataItem = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$row = [
			'smw_id'  => 42,
			'smw_iw'  => '',
			'smw_rev' => 9999
		];

		$this->rebuilder->expects( $this->any() )
			->method( 'ping' )
			->will( $this->returnValue( true ) );

		$this->rebuilder->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( [ new FakeResultWrapper( [ (object)$row ] ), 2 ] ) );

		$this->entityIdManager->expects( $this->any() )
			->method( 'getDataItemById' )
			->will( $this->returnValue( $dataItem ) );

		$instance = new UpdateEntityCollationComplete(
			$this->store,
			$this->messageReporter
		);

		$instance->setCountDown( 0 );
		$instance->runUpdate( $this->rebuilder );

		$this->assertContains(
			'updating document                           100% (1/1)',
			$this->messageReporter->getMessagesAsString()
		);
	}

}
