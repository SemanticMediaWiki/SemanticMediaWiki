<?php

namespace SMW\Test;
use SMW\DISerializer;
use SMWQueryProcessor;

/**
 * Tests for the SMW\DISerializer class
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
class DISerializerTest extends \MediaWikiTestCase {

	/**
	 * Helper function to build a query
	 *
	 */
	protected function getQuery( $queryString, $parameters, array $printouts ) {
		SMWQueryProcessor::addThisPrintout( $printouts, $parameters );
		$parameters = SMWQueryProcessor::getProcessedParams( $parameters, $printouts );

		return SMWQueryProcessor::createQuery(
			$queryString,
			$parameters,
			SMWQueryProcessor::SPECIAL_PAGE,
			'',
			$printouts
		);
	}

	/**
	 * Helper function to fetch the query results
	 *
	 */
	protected function getQueryResult( $queryString ) {
		$rawParams = preg_split( "/(?<=[^\|])\|(?=[^\|])/", $queryString );
		list( $queryString, $parameters, $printouts ) = SMWQueryProcessor::getComponentsFromFunctionParams( $rawParams, false );

		return smwfGetStore()->getQueryResult( $this->getQuery( $queryString, $parameters, $printouts ) );
	}

	/**
	 * @covers DISerializer::getSerializedQueryResult
	 * @covers SMWQueryResult::toArray
	 *
	 * @since  1.9
	 */
	public function testSerializedQueryResult( ) {

		$query = '[[Modification date::+]]|?Modification date|limit=10';

		$queryResult = $this->getQueryResult( $query );
		$this->assertInstanceOf( '\SMWQueryResult', $queryResult );

		$results = DISerializer::getSerializedQueryResult( $queryResult );
		$this->assertTrue( is_array( $results ) );

		$printrequests[0] = array( 'label'=> '', 'typeid' => '_wpg', 'mode' => 2 );
		$printrequests[1] = array( 'label'=> 'Modification date', 'typeid' => '_dat', 'mode' => 1 );

		$this->assertEquals( $results['printrequests'][0], $printrequests[0] );
		$this->assertEquals( $results['printrequests'][1], $printrequests[1] );

		$queryResultToArray = $queryResult->toArray();

		$this->assertEquals( $queryResultToArray['printrequests'][0], $printrequests[0] );
		$this->assertEquals( $queryResultToArray['printrequests'][1], $printrequests[1] );

	}
}