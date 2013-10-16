<?php

namespace SMW\Test;

use SMW\StoreFactory;
use SMW\StoreUpdater;
use SMW\SemanticData;
use SMW\BaseContext;
use SMW\DIWikiPage;

use Title;

/**
 * @covers \SMW\StoreUpdater
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @group SMW
 * @group SMWExtension
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
			'smwgEnableUpdateJobs'            => false,
			'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true )
		) );

		$context   = new BaseContext();

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
	public function testDoUpdate() {

		$store = StoreFactory::getStore();
		$data  = new SemanticData( $this->newSubject() );

		$instance = $this->newInstance( $store, $data );
		$instance->setUpdateStatus( false );

		$this->assertTrue( $instance->doUpdate() );

	}

	/**
	 * @dataProvider titleDataProvider
	 *
	 * @since 1.9
	 */
	public function testDoUpdateOnMock( $setup, $expected ) {

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'updateData' => function( SemanticData $data ) {
				return $data->getSubject()->getTitle()->getText() === 'Lila' ? $data->mockCallback = 'clear' : null;
			},
			'clearData'  => function( DIWikiPage $di ) {
				return $di->getTitle()->getText() === 'ClearData' ? $di->mockCallback = 'clear' : null;
			},
		) );

		$mockSubject = $this->newMockBuilder()->newObject( 'DIWikiPage', array(
			'getTitle' => $setup['title']
		) );

		$mockData = $this->newMockBuilder()->newObject( 'SemanticData', array(
			'getSubject' => $mockSubject,
		) );

		$instance = $this->newInstance( $mockStore, $mockData );
		$instance->setUpdateStatus( $setup['updateStatus'] );

		$this->assertEquals( $expected['return'], $instance->doUpdate() );

		// Callback adds a property, this is only done for this test to verify
		// that an expected function did run through the mock object
		if ( $expected['mockCallback'] ) {
			$this->assertEquals( $expected['mockCallback'], $mockData->getSubject()->mockCallback );
		}
	}

	/**
	 * @return array
	 */
	public function titleDataProvider() {

		$provider = array();

		// #0 Clear data, updateStatus = false
		$provider[] = array(
			array(
				'title'        => $this->newTitle( NS_MAIN, 'ClearData' ),
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
				'title'        => $this->newTitle( NS_MAIN, 'ClearData' ),
				'updateStatus' => true
			),
			array(
				'return'       => true,
				'mockCallback' => 'clear'
			)
		);

		// FIXME $wikiPage = WikiPage::factory( $title );
		//
		// Needs DI framework to create wikipage object in order
		// to inject a revision, as for the momement it can't
		// be tested

		// #2 Specialpage
		$title = $this->newMockBuilder()->newObject( 'Title', array(
			'isSpecialPage' => true
		) );

		$provider[] = array(
			array(
				'title'        => $title,
				'updateStatus' => false
			),
			array(
				'return'       => false,
				'mockCallback' => false
			)
		);

		return $provider;

	}

	public function testDoUpdateForTitleInUnknownNs() {
		$wikiPage = new DIWikiPage(
			'Foo',
			-7201010, // This namespace does not exist
			''
		);

		$updater = $this->newInstance( null, new SemanticData( $wikiPage ) );

		$this->assertInternalType( 'boolean', $updater->doUpdate() );
	}

	public function testDoUpdateForSpecialPage() {
		$wikiPage = new DIWikiPage(
			'Foo',
			NS_SPECIAL,
			''
		);

		$this->assertFalse(
			$this->newInstance( null, new SemanticData( $wikiPage ) )->doUpdate()
		);
	}

}
