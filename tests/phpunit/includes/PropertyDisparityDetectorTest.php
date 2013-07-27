<?php

namespace SMW\Test;

use SMW\PropertyDisparityDetector;
use SMW\DIProperty;

use Title;

/**
 * Tests for the PropertyDisparityDetector class
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\PropertyDisparityDetector
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class PropertyDisparityDetectorTest extends SemanticMediaWikiTestCase {

	/** @var DIWikiPage[] */
	protected $storeValues;

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\PropertyDisparityDetector';
	}

	/**
	 * Helper method that returns a PropertyDisparityDetector object
	 *
	 * @since 1.9
	 *
	 * @param $store
	 * @param $data
	 * @param $setting
	 *
	 * @return PropertyDisparityDetector
	 */
	private function getInstance( $store = array(), $data = array(), $setting = null ) {

		$mockStore = $this->newMockObject( $store )->getMockStore();
		$mockData  = $this->newMockObject( $data )->getMockSemanticData();
		$settings  = $this->getSettings( array(
			'smwgDeclarationProperties' => $setting === null ? array( '_PVAL' ): $setting
		) );

		return new PropertyDisparityDetector( $mockStore, $mockData, $settings );
	}

	/**
	 * @test PropertyDisparityDetector::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test PropertyDisparityDetector::detectDisparity
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

		$this->assertInstanceOf( $this->getClass(), $instance->detectDisparity() );
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
