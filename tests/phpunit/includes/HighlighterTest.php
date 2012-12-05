<?php

namespace SMW\Test;
use SMW\Highlighter;

/**
 * Tests for the SMW\Highlighter class
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
class HighlighterTest extends \MediaWikiTestCase {

	public function getTypeDataProvider() {
		return array(
			array( '' , Highlighter::TYPE_NOTYPE ),
			array( 'property', Highlighter::TYPE_PROPERTY ),
			array( 'PROPERTY', Highlighter::TYPE_PROPERTY ),
			array( 'PrOpErTy', Highlighter::TYPE_PROPERTY ),
			array( 'バカなテスト', Highlighter::TYPE_NOTYPE ),
			array( '<span>Something that should not work</span>', Highlighter::TYPE_NOTYPE ),
			array( Highlighter::TYPE_PROPERTY, Highlighter::TYPE_NOTYPE )
		);
	}

	/**
	 * @covers Highlighter::factory
	 * @dataProvider getTypeDataProvider
	 */
	public function testFactory( $type ) {
		$instance = Highlighter::factory( $type );

		$this->assertInstanceOf( 'SMW\Highlighter', $instance );
	}

	/**
	 * @covers Highlighter::getTypeId
	 * @dataProvider getTypeDataProvider
	 */
	public function testGetTypeId( $type, $expected ) {
		$results = Highlighter::getTypeId( $type );

		$this->assertTrue( is_int( $results ) );
		$this->assertEquals( $expected, $results );
	}

	/**
	 * @covers Highlighter::getHtml
	 * @dataProvider getTypeDataProvider
	 */
	public function testGetHtml( $type ) {
		$instance = Highlighter::factory( $type );

		$instance->setContent( array(
			'title' => \Title::newMainPage()->getFullText()
		) );

		// Check without caption/content set
		$this->assertTrue( is_string( $instance->getHtml() ) );

		$instance->setContent( array(
			'caption' => '123',
			'content' => 'ABC',
		) );

		// Check with caption/content set
		$this->assertTrue( is_string( $instance->getHtml() ) );
	}
}