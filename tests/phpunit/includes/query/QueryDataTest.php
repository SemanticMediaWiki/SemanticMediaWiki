<?php

namespace SMW\Test;

use SMW\QueryData;

use SMWQueryProcessor;
use Title;

/**
 * Tests for the SMW\QueryData class
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

/**
 * Tests for the SMW\QueryData class
 *
 * @ingroup SMW
 * @ingroup Test
 */
class QueryDataTest extends SemanticMediaWikiTestCase {

	/**
	 * Helper method
	 *
	 * @return string
	 */
	public function getClass() {
		return '\SMW\QueryData';
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

	/**
	 * Helper method to get queryProcessor object
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
	 * Helper method
	 *
	 * @return SMW\QueryData
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
	 * Test instance exception
	 * @test QueryData::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructorException() {
		$this->setExpectedException( 'PHPUnit_Framework_Error' );
		$instance = $this->getInstance();
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
	 * @test QueryData::add
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 * @param array $expected
	 */
	public function testQueryIdException( array $params, array $expected ) {
		$this->setExpectedException( 'MWException' );
		$title = $this->getTitle();
		$instance = $this->getInstance( $title );

		list( $query, $formattedParams ) = $this->getQueryProcessor( $params );
		$instance->add( $query, $formattedParams );
	}
}
