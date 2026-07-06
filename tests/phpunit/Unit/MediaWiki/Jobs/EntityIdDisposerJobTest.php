<?php

namespace SMW\Tests\Unit\MediaWiki\Jobs;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionManager;
use SMW\DataItems\WikiPage;
use SMW\IteratorFactory;
use SMW\Iterators\ResultIterator;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\EntityIdDisposerJob;
use SMW\SQLStore\PropertyTableIdReferenceDisposer;
use SMW\SQLStore\SQLStore;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;

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

	use MockSelectQueryBuilderTrait;

	private $connection;

	protected function setUp(): void {
		parent::setUp();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockSelectQueryBuilder() );
	}

	private function newStore(): SQLStore {
		$store = $this->getMockBuilder( SQLStore::class )
			->getMockForAbstractClass();

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$store->setConnectionManager( $connectionManager );

		return $store;
	}

	private function newIteratorFactory(): IteratorFactory {
		return new IteratorFactory();
	}

	private function newJobFactory(): JobFactory {
		$jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$nextJob = $this->getMockBuilder( EntityIdDisposerJob::class )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->any() )
			->method( 'newEntityIdDisposerJob' )
			->willReturn( $nextJob );

		return $jobFactory;
	}

	private function newJob( Title $title, array $params = [] ): EntityIdDisposerJob {
		return new EntityIdDisposerJob(
			$title,
			$params,
			$this->newStore(),
			$this->newIteratorFactory(),
			$this->newJobFactory()
		);
	}

	public function testCanConstruct() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			EntityIdDisposerJob::class,
			$this->newJob( $title )
		);
	}

	public function testCanConstructOutdatedEntitiesResultIterator() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = $this->newJob( $title );

		$this->assertInstanceOf(
			ResultIterator::class,
			$instance->newOutdatedEntitiesResultIterator()
		);
	}

	public function testCanConstructByNamespaceInvalidEntitiesResultIterator() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = $this->newJob( $title );

		$this->assertInstanceOf(
			ResultIterator::class,
			$instance->newByNamespaceInvalidEntitiesResultIterator()
		);
	}

	public function testCanConstructOutdatedQueryLinksResultIterator() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = $this->newJob( $title );

		$this->assertInstanceOf(
			ResultIterator::class,
			$instance->newOutdatedQueryLinksResultIterator()
		);
	}

	public function testCanConstructUnassignedQueryLinksResultIterator() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = $this->newJob( $title );

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

		$qb = $this->createMockSelectQueryBuilder( [ (object)$row ] );
		$this->connection->expects( $this->any() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $qb );

		$subject = WikiPage::newFromText( __METHOD__ );

		$instance = $this->newJob( $subject->getTitle(), $parameters );

		$this->assertTrue(
			$instance->run()
		);
	}

	public function testDisposeList() {
		$disposer = $this->getMockBuilder( PropertyTableIdReferenceDisposer::class )
			->disableOriginalConstructor()
			->getMock();

		$disposer->expects( $this->once() )
			->method( 'cleanUpTableEntriesByIdList' )
			->with( [ 1, 2 ] );

		$store = $this->getMockBuilder( SQLStore::class )
			->onlyMethods( [ 'service' ] )
			->getMockForAbstractClass();

		$connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->connection );

		$store->setConnectionManager( $connectionManager );

		$store->expects( $this->any() )
			->method( 'service' )
			->with( 'PropertyTableIdReferenceDisposer' )
			->willReturn( $disposer );

		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$row1 = new \stdClass;
		$row1->smw_id = 1;
		$row2 = new \stdClass;
		$row2->smw_id = 2;

		$instance = new EntityIdDisposerJob(
			$title,
			[],
			$store,
			$this->newIteratorFactory(),
			$this->newJobFactory()
		);

		$instance->disposeList( [ $row1, $row2 ] );
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
