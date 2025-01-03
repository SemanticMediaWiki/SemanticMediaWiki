<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyStatisticsRebuildJobTest extends \PHPUnit\Framework\TestCase {

	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();

		$row = new \stdClass;
		$row->smw_title = 'Test';
		$row->smw_id = 42;

		$this->testEnvironment = new TestEnvironment();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( new \Wikimedia\Rdbms\FakeResultWrapper( [ $row ] ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
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
		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\MediaWiki\Jobs\PropertyStatisticsRebuildJob',
			new PropertyStatisticsRebuildJob( $title )
		);
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testRunJob( $parameters ) {
		$subject = DIWikiPage::newFromText( __METHOD__ );

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
