<?php

namespace SMW\Test;

use SMW\StoreUpdater;
use SMW\SemanticData;
use SMW\DIWikiPage;

use Title;

/**
 * Tests for the StoreUpdater class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\StoreUpdater
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class StoreUpdaterTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\StoreUpdater';
	}

	/**
	 * Helper method that returns a StoreUpdater object
	 *
	 * @since 1.9
	 *
	 * @param $store
	 * @param $data
	 * @param $settings
	 *
	 * @return StoreUpdater
	 */
	private function newInstance( $store = null, $data = null, $settings = null ) {

		if ( $store === null ) {
			$store = $this->newMockBuilder()->newObject( 'Store' );
		}

		if ( $data === null ) {
			$data = $this->newMockBuilder()->newObject( 'SemanticData' );
		}

		if ( $settings === null ) {
			$settings  = $this->getSettings( array(
				'smwgEnableUpdateJobs'            => false,
				'smwgNamespacesWithSemanticLinks' => array( NS_MAIN => true )
			) );
		}

		return new StoreUpdater( $store, $data, $settings );
	}

	/**
	 * @test StoreUpdater::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test StoreUpdater::doUpdate
	 *
	 * @since 1.9
	 */
	public function testDoUpdate() {

		$store = \SMW\StoreFactory::getStore();
		$data  = new \SMW\SemanticData( $this->newSubject() );

		$instance = $this->newInstance( $store, $data );
		$instance->setUpdateStatus( false );

		$this->assertTrue( $instance->doUpdate() );

	}

	/**
	 * @test StoreUpdater::doUpdate
	 * @dataProvider titleDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $setup
	 * @param $expected
	 */
	public function testDoUpdateOnMock( $setup, $expected ) {

		$mockStore = $this->newMockBuilder()->newObject( 'Store', array(
			'updateData' => array( $this, 'mockStoreUpdateDataCallback' ),
			'clearData'  => array( $this, 'mockStoreClearDataCallback' ),
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

	/**
	 * @since  1.9
	 */
	public function mockStoreUpdateDataCallback( SemanticData $data ) {
		return $data->getSubject()->getTitle()->getText() === 'Lila' ? $data->mockCallback = 'clear' : null;
	}

	/**
	 * @since  1.9
	 */
	public function mockStoreClearDataCallback( DIWikiPage $di ) {
		return $di->getTitle()->getText() === 'ClearData' ? $di->mockCallback = 'clear' : null;
	}

}
