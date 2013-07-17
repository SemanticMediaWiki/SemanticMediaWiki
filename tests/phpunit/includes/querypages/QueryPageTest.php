<?php

namespace SMW\Test;

/**
 * Tests for the QueryPage class
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
 * @covers \SMW\QueryPage
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class QueryPageTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\QueryPage';
	}

	/**
	 * Helper method that returns a QueryPage object
	 *
	 * @since 1.9
	 *
	 * @param $result
	 *
	 * @return QueryPage
	 */
	private function getInstance( $search = '' ) {

		$queryPage = $this->getMockBuilder( $this->getClass() )
			->setMethods( array( 'getResults', 'formatResult' ) )
			->getMock();

		$context = $this->newContext( array( 'property' => $search ) );
		$context->setTitle( $this->getTitle() );

		$queryPage->setContext( $context );

		return $queryPage;
	}

	/**
	 * @test QueryPage::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance();
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test QueryPage::linkParameters
	 * @dataProvider linkParametersDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $test
	 * @param $expected
	 */
	public function testLinkParameters( $test, $expected ) {

		$search = $this->getRandomString();
		$result = $this->getInstance( $test )->linkParameters();

		$this->assertInternalType( 'array', $result );
		$this->assertEquals( $expected , $result );

	}

	/**
	 * @test QueryPage::getSearchForm
	 *
	 * @since 1.9
	 */
	public function testGetSearchForm() {

		$search = $this->getRandomString();
		$result = $this->getInstance()->getSearchForm( $search );

		$matcher = array(
			'tag' => 'form',
			'descendant' => array(
				'tag' => 'input',
				'attributes' => array( 'name' => 'property', 'value' => $search )
			)
		);

		$this->assertInternalType( 'string', $result );
		$this->assertTag( $matcher, $result );
	}

	/**
	 * Provides sample data to be tested
	 *
	 * @return array
	 */
	public function linkParametersDataProvider() {
		$random = $this->getRandomString();

		return array(
			array( ''      , array() ),
			array( null    , array() ),
			array( $random , array( 'property' => $random ) ),
			array( "[{$random}]" , array( 'property' => "[{$random}]" ) ),
			array( "[&{$random}...]" , array( 'property' => "[&{$random}...]" ) )
		);
	}
}
