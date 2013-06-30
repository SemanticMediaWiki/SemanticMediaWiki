<?php

namespace SMW\Test;

use SMWWantedPropertiesPage;
use SMWDataItem;

use RequestContext;

/**
 * Tests for the WantedPropertiesPage class
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
 * @since 1.9
 *
 * @file
 *
 * @license GNU GPL v2+
 * @author mwjames
 */

/**
 * @covers SMWWantedPropertiesPage
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class WantedPropertiesPageTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMWWantedPropertiesPage';
	}

	/**
	 * Helper method that returns a Store object
	 *
	 * @since 1.9
	 *
	 * @param $values
	 *
	 * @return Store
	 */
	private function getMockStore( array $values = array() ) {

		$store = $this->getMock( '\SMW\Store' );

		$store->expects( $this->any() )
			->method( 'getPropertyValues' )
			->will( $this->returnValue( $values ) );

		return $store;
	}

	/**
	 * Helper method that returns a DIWikiPage object
	 *
	 * @since 1.9
	 *
	 * @return DIWikiPage
	 */
	private function getMockDIWikiPage() {

		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$subject->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->getTitle() ) );

		$subject->expects( $this->any() )
			->method( 'getDIType' )
			->will( $this->returnValue( SMWDataItem::TYPE_WIKIPAGE ) );

		return $subject;
	}

	/**
	 * Helper method that returns a DIProperty object
	 *
	 * @since 1.9
	 *
	 * @param $isUserDefined
	 *
	 * @return DIProperty
	 */
	private function getMockDIProperty( $isUserDefined ) {

		$property = $this->getMockBuilder( '\SMW\DIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$property->expects( $this->any() )
			->method( 'isUserDefined' )
			->will( $this->returnValue( $isUserDefined ) );

		$property->expects( $this->any() )
			->method( 'getDiWikiPage' )
			->will( $this->returnValue( $this->getMockDIWikiPage() ) );

		$property->expects( $this->any() )
			->method( 'findPropertyTypeID' )
			->will( $this->returnValue( '_wpg' ) );

		$property->expects( $this->any() )
			->method( 'getKey' )
			->will( $this->returnValue( '_wpg' ) );

		$property->expects( $this->any() )
			->method( 'getDIType' )
			->will( $this->returnValue( SMWDataItem::TYPE_PROPERTY ) );

		$property->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( $this->getRandomString() ) );

		return $property;
	}

	/**
	 * Helper method that returns a Collector object
	 *
	 * @since 1.9
	 *
	 * @param $result
	 *
	 * @return Collector
	 */
	private function getMockCollector( $result = null ) {

		$collector = $this->getMockBuilder( '\SMW\Store\Collector' )
			->setMethods( array( 'cacheAccessor', 'doCollect', 'getResults' ) )
			->getMock();

		$collector->expects( $this->any() )
			->method( 'getResults' )
			->will( $this->returnValue( $result ) );

		return $collector;
	}

	/**
	 * Helper method that returns a SMWWantedPropertiesPage object
	 *
	 * @since 1.9
	 *
	 * @param $result
	 *
	 * @return SMWWantedPropertiesPage
	 */
	private function getInstance( $result = null ) {

		// Store stub object
		$store = $this->getMockStore();

		$store->expects( $this->any() )
			->method( 'getWantedPropertiesSpecial' )
			->will( $this->returnValue( $this->getMockCollector( $result ) ) );

		$instance = new SMWWantedPropertiesPage( $store, $this->getSettings() );
		$instance->setContext( RequestContext::getMain() );

		return $instance;
	}

	/**
	 * @test SMWWantedPropertiesPage::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance();
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test SMWWantedPropertiesPage::formatResult
	 * @dataProvider getUserDefinedDataProvider
	 *
	 * @since 1.9
	 */
	public function testFormatResult( $isUserDefined ) {

		$instance = $this->getInstance();
		$skin     = $this->getMock( 'Skin' );

		$count    = rand();
		$expected = $isUserDefined ? (string)$count : '';
		$property = $this->getMockDIProperty( $isUserDefined );
		$result   = $instance->formatResult( $skin, array( $property, $count ) );

		$this->assertInternalType( 'string', $result );
		$isUserDefined ? $this->assertContains( $expected, $result ) : $this->assertEmpty( $result );

	}

	/**
	 * isUserDefined switcher
	 *
	 * @return array
	 */
	public function getUserDefinedDataProvider() {
		return array( array( true ), array( false ) );
	}

	/**
	 * @test SMWWantedPropertiesPage::getResults
	 *
	 * @since 1.9
	 */
	public function testGetResults() {

		$expected = 'Lala';
		$instance = $this->getInstance( $expected );

		$this->assertEquals( $expected, $instance->getResults( null ) );

	}
}
