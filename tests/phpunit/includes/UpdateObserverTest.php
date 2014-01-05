<?php

namespace SMW\Test;

use SMW\UpdateObserver;
use SMW\ExtensionContext;

/**
 * @covers \SMW\UpdateObserver
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
class UpdateObserverTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\UpdateObserver';
	}

	/**
	 * @since 1.9
	 *
	 * @return UpdateObserver
	 */
	private function newInstance( $settings = array() ) {

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'getAllPropertySubjects' => array(),
			'getPropertySubjects'    => array(),
			'getProperties'          => array()
		) );

		$context   = new ExtensionContext();

		$container = $context->getDependencyBuilder()->getContainer();
		$container->registerObject( 'Store', $mockStore );
		$container->registerObject( 'Settings', $this->newSettings( $settings ) );

		$instance = new UpdateObserver();
		$instance->invokeContext( $context );

		return $instance;
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testDefaultContext() {
		$instance = new UpdateObserver();
		$this->assertInstanceOf( '\SMW\ContextResource', $instance->withContext() );
	}

	/**
	 * @dataProvider updateDispatcherDataProvider
	 *
	 * @since 1.9
	 */
	public function testUpdateDispatcherJob( $setup, $expected ) {

		$instance = $this->newInstance( $setup['settings'] );

		$this->assertTrue(
			$instance->runUpdateDispatcher( $setup['title'] ),
			'Asserts that runUpdateDispatcher always returns true'
		);
	}

	/**
	 * @dataProvider storeUpdaterDataProvider
	 *
	 * @since 1.9
	 */
	public function testStoreUpdater( $setup, $expected ) {

		$instance = $this->newInstance( $setup['settings'] );

		$this->assertTrue(
			$instance->runStoreUpdater( $setup['parserData'] ),
			'Asserts that runStoreUpdater always returns true'
		);
	}

	/**
	 * @return array
	 */
	public function storeUpdaterDataProvider() {

		$subject  = $this->newSubject();
		$mockData = $this->newMockBuilder()->newObject( 'SemanticData', array(
			'getSubject' => $subject
		) );

		$parserData = $this->newMockBuilder()->newObject( 'ParserData', array(
			'getData'    => $mockData,
		) );

		$provider = array();

		// #0
		$provider[] = array(
			array(
				'settings'   => array(
					'smwgEnableUpdateJobs'            => false,
					'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true )
				),
				'parserData' => $parserData
			),
			array()
		);

		return $provider;

	}

	/**
	 * @note smwgEnableUpdateJobs is set false to avoid having a Job being
	 * pushed into the "real" JobQueue
	 *
	 * @return array
	 */
	public function updateDispatcherDataProvider() {

		$title = $this->newMockBuilder()->newObject( 'TitleAccess', array(
			'getTitle' => $this->newTitle()
		) );

		$provider = array();

		// #0
		$provider[] = array(
			array(
				'settings' => array(
					'smwgEnableUpdateJobs'       => false,
					'smwgDeferredPropertyUpdate' => false
				),
				'title' => $title
			),
			array()
		);

		// #1
		$provider[] = array(
			array(
				'settings' => array(
					'smwgEnableUpdateJobs'       => false,
					'smwgDeferredPropertyUpdate' => true
				),
				'title' => $title
			),
			array()
		);

		// #2
		$title = $this->newMockBuilder()->newObject( 'TitleAccess', array(
			'getTitle' => $this->newTitle( SMW_NS_PROPERTY )
		) );

		$provider[] = array(
			array(
				'settings' => array(
					'smwgEnableUpdateJobs'       => false,
					'smwgDeferredPropertyUpdate' => true
				),
				'title' => $title
			),
			array()
		);

		// #3
		$title = $this->newMockBuilder()->newObject( 'TitleAccess', array(
			'getTitle' => $this->newTitle( SMW_NS_PROPERTY )
		) );

		$provider[] = array(
			array(
				'settings' => array(
					'smwgEnableUpdateJobs'       => false,
					'smwgDeferredPropertyUpdate' => false
				),
				'title' => $title
			),
			array()
		);

		return $provider;
	}

}
