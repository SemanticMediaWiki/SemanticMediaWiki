<?php

namespace SMW\Test;

use SMW\WantedPropertiesQueryPage;
use SMWDataItem;

/**
 * Tests for the WantedPropertiesQueryPage class
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
 * @covers \SMW\WantedPropertiesQueryPage
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class WantedPropertiesQueryPageTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\WantedPropertiesQueryPage';
	}

	/**
	 * Helper method that returns a DIWikiPage object
	 *
	 * @since 1.9
	 *
	 * @return DIWikiPage
	 */
	private function getMockDIWikiPage( $exists = true ) {

		$text  = $this->getRandomString();

		$title = $this->newMockObject( array(
			'exists'  => $exists,
			'getText' => $text,
			'getNamespace'    => NS_MAIN,
			'getPrefixedText' => $text
		) )->getMockTitle();

		$diWikiPage = $this->newMockObject( array(
			'getTitle'  => $title,
		) )->getMockDIWikiPage();

		return $diWikiPage;
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
	 * Helper method that returns a WantedPropertiesQueryPage object
	 *
	 * @since 1.9
	 *
	 * @param $result
	 *
	 * @return WantedPropertiesQueryPage
	 */
	private function getInstance( $result = null ) {

		$store = $this->newMockObject( array(
			'getPropertyValues'          => array(),
			'getWantedPropertiesSpecial' => $this->getMockCollector( $result )
		) )->getMockStore();

		$instance = new WantedPropertiesQueryPage( $store, $this->getSettings() );
		$instance->setContext( $this->newContext() );

		return $instance;
	}

	/**
	 * @test WantedPropertiesQueryPage::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance();
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test WantedPropertiesQueryPage::formatResult
	 * @dataProvider getUserDefinedDataProvider
	 *
	 * @since 1.9
	 */
	public function testFormatResult( $isUserDefined ) {

		$instance = $this->getInstance();
		$skin     = $this->getMock( 'Skin' );

		$count    = rand();
		$property = $this->newMockObject( array(
			'isUserDefined' => $isUserDefined,
			'getDiWikiPage' => $this->getMockDIWikiPage( true ),
			'getLabel'      => $this->getRandomString(),
		) )->getMockDIProperty();

		$expected = $isUserDefined ? (string)$count : '';
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
	 * @test WantedPropertiesQueryPage::getResults
	 *
	 * @since 1.9
	 */
	public function testGetResults() {

		$expected = 'Lala';
		$instance = $this->getInstance( $expected );

		$this->assertEquals( $expected, $instance->getResults( null ) );

	}
}
