<?php

namespace SMW\Test;

use SMW\StoreFactory;
use SMW\StoreUpdater;
use SMW\SemanticData;
use SMW\ExtensionContext;
use SMW\DIWikiPage;

use Title;

/**
 * @covers \SMW\StoreUpdater
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class StoreUpdaterTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string
	 */
	public function getClass() {
		return '\SMW\StoreUpdater';
	}

	/**
	 * @since 1.9
	 *
	 * @return StoreUpdater
	 */
	private function newInstance( $store = null, $data = null ) {

		if ( $store === null ) {
			$store = $this->newMockBuilder()->newObject( 'Store' );
		}

		if ( $data === null ) {
			$data = $this->newMockBuilder()->newObject( 'SemanticData' );
		}

		$settings  = $this->newSettings( array(
			'smwgPageSpecialProperties'       => array(),
			'smwgEnableUpdateJobs'            => false,
			'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true )
		) );

		$context   = new ExtensionContext();

		$container = $context->getDependencyBuilder()->getContainer();
		$container->registerObject( 'Store', $store );
		$container->registerObject( 'Settings', $settings );

		return new StoreUpdater( $data, $context );
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
	public function testRunUpdaterOnSQLStore() {

		$store = StoreFactory::getStore();
		$data  = new SemanticData( $this->newSubject() );

		$instance = $this->newInstance( $store, $data );
		$instance->setUpdateJobs( false );

		$this->assertTrue( $instance->runUpdater() );

	}

	/**
	 * @dataProvider titleDataProvider
	 *
	 * @since 1.9
	 */
	public function testRunUpdaterOnMock( $setup, $expected ) {

		$instance = $this->newInstance( $setup['store'], $setup['data'] );
		$instance->setUpdateJobs( $setup['updateStatus'] );

		$reflector = $this->newReflector();
		$performUpdate = $reflector->getMethod( 'performUpdate' );
		$performUpdate->setAccessible( true );

		$result = $performUpdate->invoke( $instance, $setup['wikiPage'] );

		$this->assertEquals( $expected['return'], $result );
		$this->assertMockCallback( $setup, $expected );

	}

	/**
	 * Callback adds a property, this is only done during this test in order
	 * to verify that an expected function did run through the mock object
	 */
	protected function assertMockCallback( $setup, $expected ) {

		if ( $expected['mockCallback'] ) {
			$this->assertEquals( $expected['mockCallback'], $setup['data']->getSubject()->mockCallback );
		}

	}

	/**
	 * @return array
	 */
	public function titleDataProvider() {

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'updateData' => function( SemanticData $data ) {
				return $data->getSubject()->getTitle()->getText() === 'UpdateData' ? $data->getSubject()->mockCallback = 'update' : null;
			},
			'clearData'  => function( DIWikiPage $di ) {
				return $di->getTitle()->getText() === 'ClearData' ? $di->mockCallback = 'clear' : null;
			},
		) );

		$provider = array();

		// #0 Clear data, updateStatus = false
		$title = $this->newTitle( NS_MAIN, 'ClearData' );

		$mockSubject = $this->newMockBuilder()->newObject( 'DIWikiPage', array(
			'getTitle' => $title
		) );

		$mockData = $this->newMockBuilder()->newObject( 'SemanticData', array(
			'getSubject' => $mockSubject,
		) );

		$mockWikiPage = $this->newMockBuilder()->newObject( 'WikiPage', array(
			'getTitle'    => $title,
			'getRevision' => null
		) );

		$provider[] = array(
			array(
				'wikiPage'     => $mockWikiPage,
				'store'        => $mockStore,
				'data'         => $mockData,
				'updateStatus' => false
			),
			array(
				'return'       => true,
				'mockCallback' => 'clear'
			)
		);

		// #1 Clear data, updateStatus = true
		$provider[] = array(
			array(
				'store'        => $mockStore,
				'data'         => $mockData,
				'wikiPage'     => $mockWikiPage,
				'updateStatus' => true
			),
			array(
				'return'       => true,
				'mockCallback' => 'clear'
			)
		);

		// #2 Update data, valid revision
		$title = $this->newTitle( NS_MAIN, 'UpdateData' );

		$mockSubject = $this->newMockBuilder()->newObject( 'DIWikiPage', array(
			'getTitle' => $title
		) );

		$mockData = $this->newMockBuilder()->newObject( 'SemanticData', array(
			'getSubject' => $mockSubject,
		) );

		$mockRevision = $this->newMockBuilder()->newObject( 'Revision', array(
			'getId'   => 9001,
			'getUser' => 'Lala'
		) );

		$mockWikiPage = $this->newMockBuilder()->newObject( 'WikiPage', array(
			'getTitle'    => $title,
			'getRevision' => $mockRevision
		) );

		$provider[] = array(
			array(
				'store'        => $mockStore,
				'data'         => $mockData,
				'wikiPage'     => $mockWikiPage,
				'updateStatus' => false
			),
			array(
				'return'       => true,
				'mockCallback' => 'update'
			)
		);

		return $provider;
	}

	/**
	 * @since 1.9
	 */
	public function testDoUpdateForTitleInUnknownNs() {
		$wikiPage = new DIWikiPage(
			'Foo',
			-7201010, // This namespace does not exist
			''
		);

		$updater = $this->newInstance( null, new SemanticData( $wikiPage ) );

		$this->assertInternalType( 'boolean', $updater->runUpdater() );
	}

	/**
	 * @since 1.9
	 */
	public function testDoUpdateForSpecialPage() {
		$wikiPage = new DIWikiPage(
			'Foo',
			NS_SPECIAL,
			''
		);

		$this->assertFalse(
			$this->newInstance( null, new SemanticData( $wikiPage ) )->runUpdater()
		);
	}

}
