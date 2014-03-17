<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\MediaWiki\Jobs\RefreshJob;
use SMW\EmptyContext;

use Title;

/**
 * @covers \SMW\MediaWiki\Jobs\RefreshJob
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class RefreshJobTest extends \PHPUnit_Framework_TestCase {

	/** @var integer */
	protected $controlRefreshDataIndex;

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
	public function testRunJobOnMockStore( $parameter, $expected ) {

		$expectedToRun = $expected['spos'] === null ? $this->never() : $this->once();

		$mockStore = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'refreshData' ) )
			->getMockForAbstractClass();

		$mockStore->expects( $expectedToRun )
			->method( 'refreshData' )
			->will( $this->returnCallback( array( $this, 'refreshDataCallback' ) ) );

		$instance = $this->acquireInstance( $mockStore, $parameter );

		$this->assertTrue( $instance->run() );

		$this->assertEquals(
			$expected['progress'],
			$instance->getProgress(),
			"Asserts that the getProgress() returns {$expected['progress']}"
		);

		$this->assertEquals(
			$expected['spos'],
			$this->controlRefreshDataIndex,
			"Asserts that the refreshData() received a spos {$expected['spos']}"
		);

		unset( $this->controlRefreshDataIndex );
	}

	/**
	 * @return array
	 */
	public function parameterDataProvider() {

		$provider = array();

		// #0 Empty
		$provider[] = array(
			array(),
			array(
				'progress' => 0,
				'spos' => null
			)
		);

		// #1 Initial
		$provider[] = array(
			array(
				'spos' => 1,
				'prog' => 0,
				'rc'   => 1
			),
			array(
				'progress' => 0,
				'spos' => 1
			)
		);

		// #2
		$provider[] = array(
			array(
				'spos' => 1,
				'run'  => 1,
				'prog' => 10,
				'rc'   => 1
			),
			array(
				'progress' => 10,
				'spos' => 1
			)
		);

		// #3 Initiates another run from the beginning
		$provider[] = array(
			array(
				'spos' => 0,
				'run'  => 1,
				'prog' => 10,
				'rc'   => 2
			),
			array(
				'progress' => 5,
				'spos' => 0
			)
		);

		return $provider;

	}

	/**
	 * @see  Store::refreshData
	 *
	 * @since  1.9
	 *
	 * @param integer $index
	 * @param integer $count
	 * @param mixed $namespaces Array or false
	 * @param boolean $usejobs
	 */
	public function refreshDataCallback( &$index, $count, $namespaces ) {
		$this->controlRefreshDataIndex = $index;
	}

	/**
	 * @return RefreshJob
	 */
	private function acquireInstance( $store = null, $parameters = array() ) {

		$title = Title::newFromText( __METHOD__ );

		if ( $store === null ) {
			$store = $this->getMockBuilder( '\SMW\Store' )
				->disableOriginalConstructor()
				->getMockForAbstractClass();
		}

		$context   = new EmptyContext();
		$container = $context->getDependencyBuilder()->getContainer();
		$container->registerObject( 'Store', $store );

		$instance = new RefreshJob( $title, $parameters );
		$instance->invokeContext( $context );
		$instance->setJobQueueEnabledState( false );

		return $instance;
	}

}
