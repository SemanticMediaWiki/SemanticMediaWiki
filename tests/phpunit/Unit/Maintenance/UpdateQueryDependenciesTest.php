<?php

namespace SMW\Tests\Unit\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use PHPUnit\Framework\TestCase;
use SMW\Maintenance\updateQueryDependencies;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\SQLStore\PropertyTableInfoFetcher;
use SMW\SQLStore\SQLStore;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use stdClass;

/**
 * @covers \SMW\Maintenance\updateQueryDependencies
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class UpdateQueryDependenciesTest extends TestCase {

	use MockSelectQueryBuilderTrait;

	private const UNREGISTERED_NAMESPACE = 3000;

	private $messageReporter;
	private $store;
	private $connection;

	protected function setUp(): void {
		$this->messageReporter = $this->getMockBuilder( MessageReporter::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			updateQueryDependencies::class,
			new updateQueryDependencies()
		);
	}

	public function testExecute() {
		$updateJob = $this->getMockBuilder( UpdateJob::class )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory = $this->getMockBuilder( JobFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$jobFactory->expects( $this->atLeastOnce() )
			->method( 'newUpdateJob' )
			->willReturn( $updateJob );

		$this->runWithRows( [ $this->newRow( NS_MAIN, 'Foo' ) ], $jobFactory );
	}

	public function testUpdatesThePagesAfterOneWhoseNamespaceNoLongerExists() {
		$updatedTitles = [];

		$jobFactory = $this->createStub( JobFactory::class );
		$jobFactory->method( 'newUpdateJob' )
			->willReturnCallback( function ( $title ) use ( &$updatedTitles ) {
				$updateJob = $this->createStub( UpdateJob::class );
				$updateJob->method( 'run' )
					->willReturnCallback( static function () use ( &$updatedTitles, $title ) {
						$updatedTitles[] = $title->getPrefixedText();
						return true;
					} );

				return $updateJob;
			} );

		$this->runWithRows(
			[ $this->newRow( self::UNREGISTERED_NAMESPACE, 'Orphan' ), $this->newRow( NS_MAIN, 'Foo' ) ],
			$jobFactory
		);

		$this->assertSame( [ 'Foo' ], $updatedTitles );
	}

	private function newRow( int $namespace, string $title ): stdClass {
		$row = new stdClass;
		$row->smw_title = $title;
		$row->smw_namespace = (string)$namespace;

		return $row;
	}

	/**
	 * @param stdClass[] $rows
	 */
	private function runWithRows( array $rows, JobFactory $jobFactory ): void {
		$propertyTableInfoFetcher = $this->getMockBuilder( PropertyTableInfoFetcher::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->method( 'newSelectQueryBuilder' )
			->willReturnCallback(
				fn () => $this->createMockSelectQueryBuilder( $rows )
			);

		$this->store->method( 'getPropertyTableInfoFetcher' )
			->willReturn( $propertyTableInfoFetcher );

		$this->store->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = new updateQueryDependencies();

		$instance->setStore( $this->store );
		$instance->setJobFactory( $jobFactory );

		$instance->setMessageReporter(
			$this->messageReporter
		);

		$instance->execute();
	}

}
