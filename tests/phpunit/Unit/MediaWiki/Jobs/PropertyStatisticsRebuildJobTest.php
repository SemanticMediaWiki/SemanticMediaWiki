<?php

namespace SMW\Tests\Unit\MediaWiki\Jobs;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;
use stdClass;

/**
 * @covers \SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyStatisticsRebuildJobTest extends TestCase {

	use MockSelectQueryBuilderTrait;
	use MockWriteQueryBuilderTrait;

	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$row = new stdClass;
		$row->smw_title = 'Test';
		$row->smw_id = 42;

		$this->testEnvironment = new TestEnvironment();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		// PropertyStatisticsRebuilder::rebuild() reads through newSelectQueryBuilder
		// and the underlying PropertyStatisticsStore writes via
		// newDeleteQueryBuilder() and newInsertQueryBuilder().
		$connection->method( 'newSelectQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockSelectQueryBuilder( [ $row ] ) );
		$connection->method( 'newDeleteQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockDeleteQueryBuilder() );
		$connection->method( 'newInsertQueryBuilder' )
			->willReturnCallback( fn () => $this->createMockInsertQueryBuilder() );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$store->expects( $this->any() )
			->method( 'getPropertyTables' )
			->willReturn( [] );

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
			PropertyStatisticsRebuildJob::class,
			new PropertyStatisticsRebuildJob( $title )
		);
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testRunJob( $parameters ) {
		$subject = WikiPage::newFromText( __METHOD__ );

		$instance = new PropertyStatisticsRebuildJob(
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

		return $provider;
	}

}
