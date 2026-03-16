<?php

namespace SMW\Tests\MediaWiki\Jobs;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionManager;
use SMW\DIWikiPage;
use SMW\Iterators\ResultIterator;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\Jobs\EntityIdDisposerJob;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Jobs\EntityIdDisposerJob
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class EntityIdDisposerJobTest extends TestCase {

	private $testEnvironment;
	private $connection;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( [ 'Foo' ] );

		$store = $this->getMockBuilder( SQLStore::class )
			->getMockForAbstractClass();

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$store->setConnectionManager( $connectionManager );

		$this->testEnvironment->registerObject( 'Store', $store );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			EntityIdDisposerJob::class,
			new EntityIdDisposerJob( $title )
		);
	}

	public function testCanConstructOutdatedEntitiesResultIterator() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EntityIdDisposerJob( $title );

		$this->assertInstanceOf(
			ResultIterator::class,
			$instance->newOutdatedEntitiesResultIterator()
		);
	}

	public function testCanConstructByNamespaceInvalidEntitiesResultIterator() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EntityIdDisposerJob( $title );

		$this->assertInstanceOf(
			ResultIterator::class,
			$instance->newByNamespaceInvalidEntitiesResultIterator()
		);
	}

	public function testCanConstructOutdatedQueryLinksResultIterator() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EntityIdDisposerJob( $title );

		$this->assertInstanceOf(
			ResultIterator::class,
			$instance->newOutdatedQueryLinksResultIterator()
		);
	}

	public function testCanConstructUnassignedQueryLinksResultIterator() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new EntityIdDisposerJob( $title );

		$this->assertInstanceOf(
			ResultIterator::class,
			$instance->newUnassignedQueryLinksResultIterator()
		);
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testJobRun( $parameters ) {
		$row = [
			'smw_id' => 42,
			'smw_title' => 'Foo',
			'smw_namespace' => NS_MAIN,
			'smw_iw' => '',
			'smw_subobject' => '',
			'smw_sort' => '',
			'smw_sortkey' => '',
			'smw_hash' => ''
		];

		$this->connection->expects( $this->any() )
			->method( 'selectRow' )
			->willReturn( (object)$row );

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new EntityIdDisposerJob(
			$subject->getTitle(),
			$parameters
		);

		$this->assertTrue(
			$instance->run()
		);
	}

	public function parametersProvider() {
		$provider[] = [
			[]
		];

		$provider[] = [
			[ 'id' => 42 ]
		];

		return $provider;
	}

}
