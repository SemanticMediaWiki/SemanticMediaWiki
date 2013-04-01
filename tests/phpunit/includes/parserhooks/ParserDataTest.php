<?php

namespace SMW\Test;

use SMW\ParserData;
use ParserOutput;
use Title;

/**
 * Tests for the SMW\ParserData class
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
 * @since 1.9
 *
 * @ingroup SMW
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */
class ParserDataTest extends \MediaWikiTestCase {

	/**
	 * Helper method to get title object
	 *
	 * @return Title
	 */
	private function getTitle( $title ){
		return Title::newFromText( $title );
	}

	/**
	 * Helper method to get ParserOutput object
	 *
	 * @return ParserOutput
	 */
	private function getParserOutput(){
		return new ParserOutput();
	}

	/**
	 * Helper method
	 *
	 * @return SMW\ParserData
	 */
	private function getInstance( $titleName, ParserOutput $parserOutput ) {
		//return new ParserData( $this->getTitle( $titleName ), $parserOutput );
	}

	/**
	 * Test instance
	 *
	 */
	public function testConstructor() {
		//$instance = $this->getInstance( 'Foo', $this->getParserOutput() );
		//$this->assertInstanceOf( 'SMW\ParserData', $instance );

		$this->markTestIncomplete( 'This test has not been implemented yet.' );
	}

	/**
	 * Returns Title object
	 *
	 * @covers SMW\ParserData::getTitle
	 *
	 * @since 1.9
	 */
	public function testGetTitle() {
		$this->markTestIncomplete( 'This test has not been implemented yet.' );
	}

	/**
	 * Returns ParserOutput object
	 *
	 * @covers SMW\ParserData::getOutput
	 *
	 * @since 1.9
	 */
	public function testGetOutput() {
		$this->markTestIncomplete( 'This test has not been implemented yet.' );
	}

	/**
	 * Update ParserOoutput with processed semantic data
	 *
	 * @covers SMW\ParserData::updateOutput
	 *
	 * @since 1.9
	 */
	public function testUpdateOutput() {
		$this->markTestIncomplete( 'This test has not been implemented yet.' );
	}

	/**
	 * Get semantic data
	 *
	 * @covers SMW\ParserData::getData
	 *
	 * @since 1.9
	 */
	public function testGetData() {
		$this->markTestIncomplete( 'This test has not been implemented yet.' );
	}

	/**
	 * Stores semantic data to the database
	 *
	 * @covers SMW\ParserData::storeData
	 *
	 * @since 1.9
	 */
	public function testStoreData() {
		$this->markTestIncomplete( 'This test has not been implemented yet.' );
	}

	/**
	 * Returns an report about activities that occurred during processing
	 *
	 * @covers SMW\ParserData::getReport
	 *
	 * @since 1.9
	 */
	public function testGetReport() {
		$this->markTestIncomplete( 'This test has not been implemented yet.' );
	}

}
