<?php

namespace SMW\Test;

/**
 * Tests for the GlobalFunctions
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
class GlobalFunctionsTest extends \MediaWikiTestCase {

	/**
	 * dataProvider
	 */
	public function getGlobalFunctions() {
		return array(
			array( 'smwfIsSemanticsProcessed' ),
			array( 'smwfNormalTitleDBKey' ),
			array( 'smwfNormalTitleText' ),
			array( 'smwfXMLContentEncode' ),
			array( 'smwfHTMLtoUTF8' ),
			array( 'smwfNumberFormat' ),
			array( 'smwfEncodeMessages' ),
			array( 'smwfGetStore' ),
			array( 'smwfGetSparqlDatabase' ),
			array( 'smwfGetLinker' ),
		) ;
	}

	/**
	 * dataProvider
	 */
	public function getEncodeMessagesDataProvider() {
		return array(
			array( array ( '', '', '' ) , '', '', true ),
			array( array ( 'abc', 'ABC', '<span>Test</span>' ) , '', '', true ),
			array( array ( 'abc', 'ABC', '<span>Test</span>' ) , 'warning', '', true ),
			array( array ( 'abc', 'ABC', '<span>Test</span>' ) , 'info', ',', false ),
			array( array ( 'abc', 'ABC', '<span>Test</span>' ) , null, ',', false ),
			array( array ( 'abc', 'ABC', '<span>Test</span>' ) , '<span>Test</span>', ',', true ),
		);
	}

	/**
	 * Test if global functions are accessible
	 *
	 * @covers global functions
	 * @dataProvider getGlobalFunctions
	 */
	public function testGlobalFunctionsAccessibility( $function ) {
		$this->assertTrue( function_exists( $function ) );
	}

	/**
	 * @covers smwfEncodeMessages
	 * @dataProvider getEncodeMessagesDataProvider
	 */
	public function testSmwfEncodeMessages( $message, $type, $seperator, $escape ) {
		$results = smwfEncodeMessages( $message );
		$this->assertFalse( is_null( $results ) );
		$this->assertTrue( is_string( $results ) );

		$results = smwfEncodeMessages( $message, $type );
		$this->assertFalse( is_null( $results ) );
		$this->assertTrue( is_string( $results ) );

		$results = smwfEncodeMessages( $message, $type, $seperator );
		$this->assertFalse( is_null( $results ) );
		$this->assertTrue( is_string( $results ) );

		$results = smwfEncodeMessages( $message, $type, $seperator, $escape );
		$this->assertFalse( is_null( $results ) );
		$this->assertTrue( is_string( $results ) );
	}
}