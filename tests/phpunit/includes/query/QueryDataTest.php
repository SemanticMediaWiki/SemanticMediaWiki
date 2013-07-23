<?php

namespace SMW\Test;

use SMW\QueryData;

use SMWQueryProcessor;
use Title;

/**
 * Tests for the QueryData class
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
 * @covers \SMW\QueryData
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class QueryDataTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\QueryData';
	}

	/**
	 * Helper method that returns a SMWQueryProcessor object
	 *
	 * @param array rawParams
	 *
	 * @return QueryProcessor
	 */
	private function getQueryProcessor( array $rawParams ) {
		return SMWQueryProcessor::getQueryAndParamsFromFunctionParams(
			$rawParams,
			SMW_OUTPUT_WIKI,
			SMWQueryProcessor::INLINE_QUERY,
			false
		);
	}

	/**
	 * Helper method that returns a QueryData object
	 *
	 * @param Title|null $title
	 *
	 * @return QueryData
	 */
	private function getInstance( Title $title = null ) {
		return new QueryData( $title );
	}

	/**
	 * @test QueryData::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance( $this->getTitle() );
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test QueryData::getProperty
	 *
	 * @since 1.9
	 */
	public function testGetProperty() {
		$instance = $this->getInstance( $this->getTitle() );
		$this->assertInstanceOf( '\SMWDIProperty', $instance->getProperty() );
	}

	/**
	 * @test QueryData::getErrors
	 *
	 * @since 1.9
	 */
	public function testGetErrors() {
		$instance = $this->getInstance( $this->getTitle() );
		$this->assertInternalType( 'array', $instance->getErrors() );
	}

	/**
	 * @test QueryData::add
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expected
	 */
	public function testInstantiatedQueryData( array $params, array $expected ) {
		$title = $this->getTitle();
		$instance = $this->getInstance( $title );

		list( $query, $formattedParams ) = $this->getQueryProcessor( $params );
		$instance->setQueryId( $params );
		$instance->add( $query, $formattedParams );

		// Check the returned instance
		$this->assertInstanceOf( 'SMWSemanticData', $instance->getContainer()->getSemanticData() );
		$this->assertSemanticData( $instance->getContainer()->getSemanticData(), $expected );
	}

	/**
	 * @test QueryData::add (Test instance exception)
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expected
	 * @throws MWException
	 */
	public function testQueryIdException( array $params, array $expected ) {

		$this->setExpectedException( '\SMW\UnknownIdException' );
		$title = $this->getTitle();
		$instance = $this->getInstance( $title );

		list( $query, $formattedParams ) = $this->getQueryProcessor( $params );
		$instance->add( $query, $formattedParams );

	}

	/**
	 * Provides data sample, the first array contains parametrized input
	 * value while the second array contains expected return results for the
	 * instantiated object.
	 *
	 * @return array
	 */
	public function getDataProvider() {
		return array(

			// #0
			// {{#ask: [[Modification date::+]]
			// |?Modification date
			// |format=list
			// }}
			array(
				array(
					'',
					'[[Modification date::+]]',
					'?Modification date',
					'format=list'
				),
				array(
					'propertyCount' => 4,
					'propertyKey' => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
					'propertyValue' => array( 'list', 1, 1, '[[Modification date::+]]' )
				)
			),

			// #1
			// {{#ask: [[Modification date::+]][[Category:Foo]]
			// |?Modification date
			// |?Has title
			// |format=list
			// }}
			array(
				array(
					'',
					'[[Modification date::+]][[Category:Foo]]',
					'?Modification date',
					'?Has title',
					'format=list'
				),
				array(
					'propertyCount' => 4,
					'propertyKey' => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
					'propertyValue' => array( 'list', 2, 1, '[[Modification date::+]] [[Category:Foo]]' )
				)
			),

			// #2 Unknown format, default table
			// {{#ask: [[Modification date::+]][[Category:Foo]]
			// |?Modification date
			// |?Has title
			// |format=bar
			// }}
			array(
				array(
					'',
					'[[Modification date::+]][[Category:Foo]]',
					'?Modification date',
					'?Has title',
					'format=bar'
				),
				array(
					'propertyCount' => 4,
					'propertyKey' => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
					'propertyValue' => array( 'table', 2, 1, '[[Modification date::+]] [[Category:Foo]]' )
				)
			),
		);
	}
}
