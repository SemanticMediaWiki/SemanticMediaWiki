<?php

namespace SMW\Test;

use SMWQueryProcessor;
use SMW\QueryData;

use SMWDIProperty;
use SMWDIBlob;
use SMWDINumber;
use SMWDataItem;
use SMWDataValueFactory;
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
class QueryDataTest extends \MediaWikiTestCase {

	/**
	 * DataProvider
	 *
	 * @return array
	 */
	public function getDataProvider() {
		return array(

			// {{#ask: [[Modification date::+]]
			// |?Modification date
			// |format=list
			// }}
			array(
				'Foo',
				array(
					'',
					'[[Modification date::+]]',
					'?Modification date',
					'format=list'
				),
				array(
					'queryCount' => 4,
					'queryKey' => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
					'queryValue' => array( 'list', 1, 1, '[[Modification date::+]]' )
				)
			),

			// {{#ask: [[Modification date::+]][[Category:Foo]]
			// |?Modification date
			// |?Has title
			// |format=list
			// }}
			array(
				'Foo',
				array(
					'',
					'[[Modification date::+]][[Category:Foo]]',
					'?Modification date',
					'?Has title',
					'format=list'
				),
				array(
					'queryCount' => 4,
					'queryKey' => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
					'queryValue' => array( 'list', 2, 1, '[[Modification date::+]] [[Category:Foo]]' )
				)
			),

			// Unknown format, default table

			// {{#ask: [[Modification date::+]][[Category:Foo]]
			// |?Modification date
			// |?Has title
			// |format=bar
			// }}
			array(
				'Foo',
				array(
					'',
					'[[Modification date::+]][[Category:Foo]]',
					'?Modification date',
					'?Has title',
					'format=bar'
				),
				array(
					'queryCount' => 4,
					'queryKey' => array( '_ASKST', '_ASKSI', '_ASKDE', '_ASKFO' ),
					'queryValue' => array( 'table', 2, 1, '[[Modification date::+]] [[Category:Foo]]' )
				)
			),

		);
	}

	/**
	 * Helper method to get title object
	 *
	 * @return Title
	 */
	private function getTitle( $title ){
		return Title::newFromText( $title );
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
	private function getInstance( $title = '' ) {
		return new QueryData( $this->getTitle( $title ) );
	}

	/**
	 * Test instance
	 *
	 * @dataProvider getDataProvider
	 */
	public function testConstructor( $title ) {
		$instance = $this->getInstance( $title );
		$this->assertInstanceOf( 'SMW\QueryData', $instance );
	}

	/**
	 * Test instance exception
	 *
	 * @dataProvider getDataProvider
	 */
	public function testConstructorException( $title ) {
		$this->setExpectedException( 'PHPUnit_Framework_Error' );
		$instance = $this->getInstance( '' );
	}

	/**
	 * Test getProperty()
	 *
	 * @dataProvider getDataProvider
	 */
	public function testGetProperty( $title ) {
		$instance = $this->getInstance( $title );
		$this->assertInstanceOf( '\SMWDIProperty', $instance->getProperty() );
	}

	/**
	 * Test generated query data
	 *
	 * @dataProvider getDataProvider
	 */
	public function testInstantiatedQueryData( $title, array $params, array $expected ) {
		$instance = $this->getInstance( $title );

		list( $query, $formattedParams ) = $this->getQueryProcessor( $params );
		$instance->setQueryId( $params );
		$instance->add(	$query, $formattedParams );

		// Check the returned instance
		$this->assertInstanceOf( 'SMWSemanticData', $instance->getContainer()->getSemanticData() );

		// Confirm subSemanticData objects for the SemanticData instance
		foreach ( $instance->getContainer()->getSemanticData()->getSubSemanticData() as $containerSemanticData ){
			$this->assertInstanceOf( 'SMWContainerSemanticData', $containerSemanticData );
			$this->assertCount( $expected['queryCount'], $containerSemanticData->getProperties() );

			// Confirm added properties
			foreach ( $containerSemanticData->getProperties() as $key => $diproperty ){
				$this->assertInstanceOf( 'SMWDIProperty', $diproperty );
				$this->assertContains( $diproperty->getKey(), $expected['queryKey'] );

				// Confirm added property values
				foreach ( $containerSemanticData->getPropertyValues( $diproperty ) as $dataItem ){
					$dataValue = SMWDataValueFactory::newDataItemValue( $dataItem, $diproperty );
					if ( $dataValue->getDataItem()->getDIType() === SMWDataItem::TYPE_WIKIPAGE ){
						$this->assertContains( $dataValue->getWikiValue(), $expected['queryValue'] );
					} else if ( $dataValue->getDataItem()->getDIType() === SMWDataItem::TYPE_NUMBER ){
						$this->assertContains( $dataValue->getNumber(), $expected['queryValue'] );
					} else if ( $dataValue->getDataItem()->getDIType() === SMWDataItem::TYPE_BLOB ){
						$this->assertContains( $dataValue->getWikiValue(), $expected['queryValue'] );
					}
				}
			}
		}
	}

	/**
	 * Test QueryId exception
	 *
	 * @dataProvider getDataProvider
	 */
	public function testQueryIdException( $title, array $params, array $expected) {
		$this->setExpectedException( 'MWException' );
		$instance = $this->getInstance( $title );

		list( $query, $formattedParams ) = $this->getQueryProcessor( $params );
		$instance->add(	$query, $formattedParams );
	}
}
