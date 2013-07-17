<?php

namespace SMW\Test;

use SMW\RedirectBuilder;
use SMW\DIProperty;

use SMWSemanticData;

/**
 * Tests for the RedirectBuilder class
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
 * @covers \SMW\RedirectBuilder
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class RedirectBuilderTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\RedirectBuilder';
	}

	/**
	 * Helper method that returns a RedirectBuilder object
	 *
	 * @since 1.9
	 *
	 * @param $data
	 *
	 * @return RedirectBuilder
	 */
	private function getInstance( SMWSemanticData $data = null ) {
		return new RedirectBuilder( $data === null ? $this->newMockObject()->getMockSemanticData() : $data );
	}

	/**
	 * @test RedirectBuilder::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test RedirectBuilder::build
	 * @dataProvider redirectsDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $test
	 * @param $expected
	 */
	public function testBuild( $test, $expected ) {

		$semanticData =  new SMWSemanticData( $this->getSubject() );

		$instance = $this->getInstance( $semanticData );
		$instance->canBuild( $test['canBuild'] )->build( $test['build'] );

		$this->assertSemanticData( $semanticData, $expected );
	}


	/**
	 * Provides redirects injection sample
	 *
	 * @return array
	 */
	public function redirectsDataProvider() {

		$title = $this->getTitle();

		// #0 Title
		$provider[] = array(
			array( 'build' => $title, 'canBuild' => true ),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_REDI',
				'propertyValue' => ':' . $title->getText()
			)
		);

		// #1 Disabled
		$provider[] = array(
			array( 'build' => $title, 'canBuild' => false ),
			array(
				'propertyCount' => 0,
			)
		);

		// #2 Free text
		$provider[] = array(
			array( 'build' => '#REDIRECT [[:Lala]]', 'canBuild' => true ),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_REDI',
				'propertyValue' => ':Lala'
			)
		);

		// #3 Free text
		$provider[] = array(
			array( 'build' => '#REDIRECT [[Lala]]', 'canBuild' => true ),
			array(
				'propertyCount' => 1,
				'propertyKey'   => '_REDI',
				'propertyValue' => ':Lala'
			)
		);

		// #4 Disabled free text
		$provider[] = array(
			array( 'build' => '#REDIRECT [[:Lala]]', 'canBuild' => false ),
			array(
				'propertyCount' => 0,
			)
		);

		// #5 Invalid free text
		$provider[] = array(
			array( 'build' => '#REDIR [[:Lala]]', 'canBuild' => true ),
			array(
				'propertyCount' => 0,
			)
		);

		// #6 Empty
		$provider[] = array(
			array( 'build' => '', 'canBuild' => true ),
			array(
				'propertyCount' => 0,
			)
		);

		return $provider;
	}

}
