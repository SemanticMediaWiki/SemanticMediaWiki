<?php

namespace SMW\Test;

use SMW\PropertyChangeNotifier;
use SMW\DIProperty;

use Title;

/**
 * Tests for the PropertyChangeNotifier class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\PropertyChangeNotifier
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class PropertyChangeNotifierTest extends SemanticMediaWikiTestCase {

	/** @var DIWikiPage[] */
	protected $storeValues;

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\PropertyChangeNotifier';
	}

	/**
	 * Helper method that returns a PropertyChangeNotifier object
	 *
	 * @since 1.9
	 *
	 * @param $store
	 * @param $data
	 * @param $setting
	 *
	 * @return PropertyChangeNotifier
	 */
	private function getInstance( $store = array(), $data = array(), $setting = null ) {

		$mockStore = $this->newMockObject( $store )->getMockStore();
		$mockData  = $this->newMockObject( $data )->getMockSemanticData();
		$settings  = $this->getSettings( array(
			'smwgDeclarationProperties' => $setting === null ? array( '_PVAL' ): $setting
		) );

		return new PropertyChangeNotifier( $mockStore, $mockData, $settings );
	}

	/**
	 * @test PropertyChangeNotifier::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test PropertyChangeNotifier::detectChanges
	 * @dataProvider dataItemDataProvider
	 *
	 * @since 1.9
	 */
	public function testFindDisparity( $storeValues, $dataValues, $settings, $expected ) {

		$subject = $this->newSubject( $this->newTitle( SMW_NS_PROPERTY ) );
		$this->storeValues = $storeValues;

		$store = array(
			'getPropertyValues' => array( $this, 'mockStorePropertyValuesCallback' ),
		);

		$data  = array(
			'getSubject'        => $subject,
			'getPropertyValues' => $dataValues
		);

		$instance = $this->getInstance( $store, $data, $settings );
		$observer = new MockChangeObserver( $instance );

		$this->assertInstanceOf( $this->getClass(), $instance->detectChanges() );
		$this->assertEquals( $subject->getTitle(), $instance->getTitle() );
		$this->assertEquals( $expected['change'], $instance->hasDisparity() );

		// Verify that the Observer was notified
		$this->assertEquals( $expected['notifier'], $observer->getNotifier() );

	}

	/**
	 * Provides array of dataItems
	 *
	 * @return array
	 */
	public function dataItemDataProvider() {

		$notifier = 'runUpdateDispatcher';

		// Single
		$subject  = array(
			$this->newSubject()
		);

		// Multiple
		$subjects = array(
			$this->newSubject(),
			$this->newSubject(),
			$this->newSubject()
		);

		return array(
			//  $storeValues, $dataValues, $settings,               $expected
			array( $subjects, array(),   array( '_PVAL', '_LIST' ), array( 'change' => true,  'notifier' => $notifier ) ),
			array( array(),   $subjects, array( '_PVAL', '_LIST' ), array( 'change' => true,  'notifier' => $notifier ) ),
			array( $subject,  $subjects, array( '_PVAL', '_LIST' ), array( 'change' => true,  'notifier' => $notifier ) ),
			array( $subject,  array(),   array( '_PVAL', '_LIST' ), array( 'change' => true,  'notifier' => $notifier ) ),
			array( $subject,  array(),   array( '_PVAL'          ), array( 'change' => true,  'notifier' => $notifier ) ),
			array( $subjects, $subjects, array( '_PVAL'          ), array( 'change' => false, 'notifier' => null      ) ),
			array( $subject,  $subject,  array( '_PVAL'          ), array( 'change' => false, 'notifier' => null      ) ),
			array( $subjects, $subjects, array( '_PVAL', '_LIST' ), array( 'change' => true,  'notifier' => $notifier ) ),
			array( $subject,  $subject,  array( '_PVAL', '_LIST' ), array( 'change' => true,  'notifier' => $notifier ) )
		);
	}

	/**
	 * Returns an array of SMWDataItem and simulates an alternating
	 * existencance of return values ('_LIST')
	 *
	 * @see Store::getPropertyValues
	 *
	 * @return SMWDataItem[]
	 */
	public function mockStorePropertyValuesCallback( $subject, DIProperty $property, $requestoptions = null ) {
		return $property->getKey() === '_LIST' ? array() : $this->storeValues;
	}

}
