<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Jobs\RefreshJob;
use Title;

/**
 * @covers \SMW\MediaWiki\Jobs\RefreshJob
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class RefreshJobTest extends \PHPUnit_Framework_TestCase {

	/** @var integer */
	protected $controlRefreshDataIndex;

	private $applicationFactory;

	protected function setUp() {
		parent::setUp();

		$this->applicationFactory = ApplicationFactory::getInstance();

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->applicationFactory->registerObject( 'Store', $store );
	}

	protected function tearDown() {
		$this->applicationFactory->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\MediaWiki\Jobs\RefreshJob',
			new RefreshJob( $title )
		);

		// FIXME Delete SMWRefreshJob assertion after all
		// references to SMWRefreshJob have been removed
		$this->assertInstanceOf(
			'SMW\MediaWiki\Jobs\RefreshJob',
			new \SMWRefreshJob( $title )
		);
	}

	/**
	 * @dataProvider parameterDataProvider
	 */
	public function testRunJobOnMockStore( $parameters, $expected ) {

		$title = Title::newFromText( __METHOD__ );

		$expectedToRun = $expected['spos'] === null ? $this->once() : $this->once();

		$rebuilder = $this->getMockBuilder( '\SMW\SQLStore\Rebuilder\Rebuilder' )
			->disableOriginalConstructor()
			->getMock();

		$rebuilder->expects( $this->any() )
			->method( 'rebuild' )
			->will( $this->returnValue( $parameters['spos'] ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->setMethods( [ 'refreshData' ] )
			->getMockForAbstractClass();

		$store->expects( $expectedToRun )
			->method( 'refreshData' )
			->will( $this->returnValue( $rebuilder ) );

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
	 * @param integer $index
	 */
	public function refreshDataCallback( &$index ) {
		$this->controlRefreshDataIndex = $index;
	}

}
