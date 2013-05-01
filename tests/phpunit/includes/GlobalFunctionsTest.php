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
 * @since 1.9
 *
 * @file
 * @ingroup SMW
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Tests for the GlobalFunctions
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class GlobalFunctionsTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return false;
	}

	/**
	 * Provides available global functions
	 *
	 * @return array
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
	 * Provides messages
	 *
	 * @return array
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
	 * @test Test if global functions are accessible
	 * @dataProvider getGlobalFunctions
	 *
	 * @param $function
	 */
	public function testGlobalFunctionsAccessibility( $function ) {
		$this->assertTrue( function_exists( $function ) );
	}

	/**
	 * @test smwfEncodeMessages
	 * @dataProvider getEncodeMessagesDataProvider
	 *
	 * @param $message
	 * @param $type
	 * @param $separator
	 * @param $escape
	 */
	public function testSmwfEncodeMessages( $message, $type, $separator, $escape ) {
		$results = smwfEncodeMessages( $message );
		$this->assertFalse( is_null( $results ) );
		$this->assertTrue( is_string( $results ) );

		$results = smwfEncodeMessages( $message, $type );
		$this->assertFalse( is_null( $results ) );
		$this->assertTrue( is_string( $results ) );

		$results = smwfEncodeMessages( $message, $type, $separator );
		$this->assertFalse( is_null( $results ) );
		$this->assertTrue( is_string( $results ) );

		$results = smwfEncodeMessages( $message, $type, $separator, $escape );
		$this->assertFalse( is_null( $results ) );
		$this->assertTrue( is_string( $results ) );
	}
}