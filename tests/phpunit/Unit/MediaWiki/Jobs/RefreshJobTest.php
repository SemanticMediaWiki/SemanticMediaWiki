<?php

namespace SMW\Tests\Unit\MediaWiki\Jobs;

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Jobs\RefreshJob;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\SQLStore\Rebuilder\Rebuilder;
use SMW\Store;

/**
 * @covers \SMW\MediaWiki\Jobs\RefreshJob
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class RefreshJobTest extends TestCase {

	/** @var int */
	protected $controlRefreshDataIndex;

	private $applicationFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->applicationFactory->registerObject( 'Store', $store );
	}

	protected function tearDown(): void {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			RefreshJob::class,
			new RefreshJob( $title )
		);
	}

	/**
	 * @dataProvider parameterDataProvider
	 */
	public function testRunJobOnMockStore( $parameters, $expected ) {
		$title = MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ );

		$expectedToRun = $expected['spos'] === null ? $this->once() : $this->once();

		$rebuilder = $this->getMockBuilder( Rebuilder::class )
			->disableOriginalConstructor()
			->getMock();

		$rebuilder->expects( $this->any() )
			->method( 'rebuild' )
			->willReturn( $parameters['spos'] ?? 0 );

		$store = $this->getMockBuilder( Store::class )
			->setMethods( [ 'refreshData' ] )
			->getMockForAbstractClass();

		$store->expects( $expectedToRun )
			->method( 'refreshData' )
			->willReturn( $rebuilder );

		$this->applicationFactory->registerObject( 'Store', $store );

		$instance = new RefreshJob( $title, $parameters );
		$instance->isEnabledJobQueue( false );

		$this->assertTrue( $instance->run() );

		$this->assertEquals(
			$expected['progress'],
			$instance->getProgress(),
			"Asserts that the getProgress() returns {$expected['progress']}"
		);
	}

	/**
	 * @return array
	 */
	public function parameterDataProvider() {
		$provider = [];

		// #0 Empty
		$provider[] = [
			[
				'spos' => null
			],
			[
				'progress' => 0,
				'spos' => null
			]
		];

		// #1 Initial
		$provider[] = [
			[
				'spos' => 1,
				'prog' => 0,
				'rc'   => 1
			],
			[
				'progress' => 0,
				'spos' => 1
			]
		];

		// #2
		$provider[] = [
			[
				'spos' => 1,
				'run'  => 1,
				'prog' => 10,
				'rc'   => 1
			],
			[
				'progress' => 10,
				'spos' => 1
			]
		];

		// #3 Initiates another run from the beginning
		$provider[] = [
			[
				'spos' => 0,
				'run'  => 1,
				'prog' => 10,
				'rc'   => 2
			],
			[
				'progress' => 5,
				'spos' => 0
			]
		];

		return $provider;
	}

	/**
	 * @see  Store::refreshData
	 *
	 * @since  1.9
	 *
	 * @param int &$index
	 */
	public function refreshDataCallback( &$index ) {
		$this->controlRefreshDataIndex = $index;
	}

}
