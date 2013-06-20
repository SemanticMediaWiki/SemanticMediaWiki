<?php

namespace SMW\Test;

use SMWWantedPropertiesPage;

use DerivativeContext;
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
	 * Helper method that returns a SMWWantedPropertiesPage object
	 *
	 * @since 1.9
	 *
	 * @param $result
	 *
	 * @return SMWWantedPropertiesPage
	 */
	private function getInstance( $result = null ) {

		$context = new DerivativeContext( RequestContext::getMain() );

		// Collector stub object
		$collector = $this->getMockForAbstractClass( '\SMW\Store\Collector' );

		$collector->expects( $this->any() )
			->method( 'getResults' )
			->will( $this->returnValue( $result ) );

		// Store stub object
		$store = $this->getMock( '\SMW\Store' );

		$store->expects( $this->any() )
			->method( 'getWantedPropertiesSpecial' )
			->will( $this->returnValue( $collector ) );

		return new SMWWantedPropertiesPage(
			$store,
			$context,
			$this->getSettings()
		);
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

		// Skin stub object
		$skin = $this->getMock( 'Skin' );

		// SMWDIWikiPage stub object
		$subject = $this->getMockBuilder( 'SMWDIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$subject->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $this->getTitle() ) );

		// SMWDIProperty stub object
		$property = $this->getMockBuilder( 'SMWDIProperty' )
			->disableOriginalConstructor()
			->getMock();

		$property->expects( $this->any() )
			->method( 'isUserDefined' )
			->will( $this->returnValue( $isUserDefined ) );

		$property->expects( $this->any() )
			->method( 'getDiWikiPage' )
			->will( $this->returnValue( $subject ) );

		$property->expects( $this->any() )
			->method( 'getLabel' )
			->will( $this->returnValue( $this->getRandomString() ) );

		$result = $instance->formatResult( $skin, array( $property, 1 ) );
		$this->assertInternalType( 'string', $result );
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
		$result = $instance->getResults( null );

		$this->assertEquals( $expected, $result );
	}
}
