<?php

namespace SMW\Test;

use ReflectionClass;
use ApiResult;

/**
 * Tests for the ApiQuery class
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
 * @covers \ApiSMWQuery
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class ApiQueryTest extends ApiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\ApiSMWQuery';
	}

	/**
	 * Helper method that returns a ApiSMWQuery object
	 *
	 * @since 1.9
	 *
	 * @param $result
	 *
	 * @return ApiSMWQuery
	 */
	private function getInstance( ApiResult $apiResult = null ) {

		$apiQuery = $this->getMockBuilder( $this->getClass() )
			->disableOriginalConstructor()
			->getMock();

		$apiQuery->expects( $this->any() )
			->method( 'getResult' )
			->will( $this->returnValue( $apiResult ) );

		return $apiQuery;
	}

	/**
	 * @test ApiSMWQuery::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test ApiSMWQuery::addQueryResult
	 *
	 * @since 1.9
	 */
	public function testAddQueryResult() {

		// Minimalistic test case to verify executability
		// For a full coverage, use ApiQueryResultFormatterTest
		$test = array(
			'results' => array(
				'Foo' => array(
					'printouts' => array( 'lula' => array( 'lila' ) )
				)
			),
			'printrequests' => array( 'Bar' ),
			'meta' => array( 'count' => 5, 'offset' => 5 )
		);

		$apiResult   = $this->getApiResult( array() );
		$queryResult = $this->newMockObject( array(
			'toArray'           => $test,
			'getErrors'         => array(),
			'hasFurtherResults' => true
		) )->getQueryResult();

		// Access protected method
		$reflector = new ReflectionClass( $this->getClass() );
		$method = $reflector->getMethod( 'addQueryResult' );
		$method->setAccessible( true );

		$instance = $this->getInstance( $apiResult );
		$method->invoke( $instance, $queryResult );

		// Test against the invoked ApiResult, as the addQueryResult method
		// does not return any actual results
		$this->assertInternalType( 'array', $apiResult->getData() );
		$this->assertEquals( array( 'query' => $test, 'query-continue-offset' => 10 ), $apiResult->getData() );

	}
}
