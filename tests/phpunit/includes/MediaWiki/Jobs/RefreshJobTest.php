<?php

namespace SMW\Test;

use SMW\EmptyContext;
use SMW\RefreshJob;

use Title;

/**
 * @covers \SMW\RefreshJob
 * @covers \SMW\JobBase
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
class RefreshJobTest extends SemanticMediaWikiTestCase {

	/** @var integer */
	protected $controlRefreshDataIndex;

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\RefreshJob';
	}

	/**
	 * @since 1.9
	 *
	 * @return RefreshJob
	 */
	private function newInstance( $store = null, $parameters = array() ) {

		if ( $store === null ) {
			$store = $this->newMockBuilder()->newObject( 'Store' );
		}

		$context   = new EmptyContext();
		$container = $context->getDependencyBuilder()->getContainer();
		$container->registerObject( 'Store', $store );

		$instance = new RefreshJob( $this->newTitle(), $parameters );
		$instance->invokeContext( $context );

		return $instance;
	}

	/**
	 * FIXME Delete SMWRefreshJob assertion after all references to
	 * SMWRefreshJob have been removed
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
		$this->assertInstanceOf( $this->getClass(), new \SMWRefreshJob( $this->newTitle() ) );
	}

	/**
	 * @dataProvider parameterDataProvider
	 *
	 * @since 1.9
	 */
	public function testRunOnMockStore( $parameter, $expected ) {

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'refreshData' => array( $this, 'refreshDataCallback' )
		) );

		$instance = $this->newInstance( $mockStore, $parameter );

		$this->assertTrue(
			$instance->disable()->run(),
			'Asserts that the run() returns true'
		);

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

}
