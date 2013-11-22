<?php

namespace SMW\Test;

use SMW\PropertyTypeComparator;
use SMW\ObservableSubjectDispatcher;
use SMW\DIProperty;

use Title;

/**
 * @covers \SMW\PropertyTypeComparator
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
class PropertyTypeComparatorTest extends SemanticMediaWikiTestCase {

	/** @var DIWikiPage[] */
	protected $storeValues;

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\PropertyTypeComparator';
	}

	/**
	 * @since 1.9
	 *
	 * @return PropertyTypeComparator
	 */
	private function newInstance( $store = array(), $data = array(), $setting = null ) {

		$mockStore = $this->newMockBuilder()->newObject( 'Store', $store );
		$mockData  = $this->newMockBuilder()->newObject( 'SemanticData', $data );
		$settings  = $this->newSettings( array(
			'smwgDeclarationProperties' => $setting === null ? array( '_PVAL' ): $setting
		) );

		return new PropertyTypeComparator( $mockStore, $mockData, $settings );
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @dataProvider dataItemDataProvider
	 *
	 * @since 1.9
	 */
	public function testDetectChanges( $storeValues, $dataValues, $settings, $expected ) {

		$subject = $this->newSubject( $this->newTitle( SMW_NS_PROPERTY ) );
		$this->storeValues = $storeValues;

		$store = array(
			'getPropertyValues' => array( $this, 'mockStorePropertyValuesCallback' ),
		);

		$data  = array(
			'getSubject'        => $subject,
			'getPropertyValues' => $dataValues
		);

		$instance = $this->newInstance( $store, $data, $settings );
		$observer = new MockUpdateObserver();

		$instance->registerDispatcher( new ObservableSubjectDispatcher( $observer ) );

		$this->assertInstanceOf( $this->getClass(), $instance->runComparator() );
		$this->assertEquals( $subject->getTitle(), $instance->getTitle() );
		$this->assertEquals( $expected['change'], $instance->hasDisparity() );

		// Verify that the Observer was notified
		$this->assertEquals( $expected['notifier'], $observer->getNotifier() );

	}

	/**
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
