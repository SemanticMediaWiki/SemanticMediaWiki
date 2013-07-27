<?php

namespace SMW\Test;

use SMW\BeforePageDisplay;

use OutputPage;
use Title;

/**
 * Tests for the BeforePageDisplay class
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
 * @covers \SMW\BeforePageDisplay
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class BeforePageDisplayTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\BeforePageDisplay';
	}

	/**
	 * Helper method that returns a OutputPage object
	 *
	 * @since 1.9
	 *
	 * @return OutputPage
	 */
	private function getOutputPage( Title $title = null ) {

		$title   = $title === null ? $this->newTitle() : $title;
		$context = $this->newContext();
		$context->setTitle( $title );
		$context->setLanguage( $this->getLanguage() );

		return new OutputPage( $context );
	}

	/**
	 * Returns a BeforePageDisplay object
	 *
	 * @since 1.9
	 */
	public function getInstance( OutputPage $outputPage ) {
		$skin = $this->newMockObject()->getMockSkin();
		return new BeforePageDisplay( $outputPage, $skin );
	}

	/**
	 * @test BeforePageDisplay::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance( $this->getOutputPage() ) );
	}

	/**
	 * @test BeforePageDisplay::process
	 * @dataProvider titleDataProvider
	 *
	 * @since 1.9
	 */
	public function testProcess( $setup, $expected ) {

		$outputPage = $this->getOutputPage( $setup['title'] );
		$result     = $this->getInstance( $outputPage )->process();

		$this->assertInternalType( 'boolean', $result );
		$this->assertTrue( $result );

		// Check if content was added to the output object
		$contains = false;

		if ( method_exists( $outputPage, 'getHeadLinksArray' ) ) {
			foreach ( $outputPage->getHeadLinksArray() as $key => $value ) {
				if ( strpos( $value, 'ExportRDF' ) ){
					$contains = true;
					break;
				};
			}
		} else{
			// MW 1.19
			if ( strpos( $outputPage->getHeadLinks(), 'ExportRDF' ) ){
				$contains = true;
			};
		}

		$expected['result'] ? $this->assertTrue( $contains ) : $this->assertFalse( $contains );
	}

	/**
	 * @return array
	 */
	public function titleDataProvider() {

		$provider = array();

		// #0 Standard title
		$provider[] = array(
			array(
				'title'  => $this->newMockObject( array(
					'isSpecialPage'   => false,
					'getPageLanguage' => $this->getLanguage(),
					'getPrefixedText' => $this->getRandomString()
				) )->getMockTitle()
			),
			array(
				'result' => true
			)
		);

		// #1 Title is SpeciaPage
		$provider[] = array(
			array(
				'title'  => $this->newMockObject( array(
					'isSpecialPage'   => true,
					'getPageLanguage' => $this->getLanguage(),
					'getPrefixedText' => $this->getRandomString()
				) )->getMockTitle()
			),
			array(
				'result' => false
			)
		);

		return $provider;
	}

}
