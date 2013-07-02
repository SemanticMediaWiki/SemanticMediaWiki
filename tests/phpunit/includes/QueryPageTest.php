<?php

namespace SMW\Test;

use RequestContext;
use FauxRequest;

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
 * @since 1.9
 *
 * @file
 *
 * @license GNU GPL v2+
 * @author mwjames
 */

/**
 * @covers \SMWQueryPage
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
		return '\SMWQueryPage';
	}

	/**
	 * Helper method that returns a SMWQueryPage object
	 *
	 * @since 1.9
	 *
	 * @param $result
	 *
	 * @return SMWQueryPage
	 */
	private function getInstance( $search = '' ) {

		$queryPage = $this->getMockBuilder( $this->getClass() )
			->setMethods( array( 'getResults', 'formatResult' ) )
			->getMock();

		$request = new FauxRequest( array( 'property' => $search ) );
		$context = new RequestContext();
		$context->setTitle( $this->getTitle() );
		$context->setRequest( $request );

		$queryPage->setContext( $context );

		return $queryPage;
	}

	/**
	 * @test SMWQueryPage::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance();
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test SMWQueryPage::linkParameters
	 *
	 * @since 1.9
	 */
	public function testLinkParameters() {

		$search = $this->getRandomString();
		$result = $this->getInstance( $search )->linkParameters();

		$this->assertInternalType( 'array', $result );
		$this->assertEquals( array( 'property' => $search ) , $result );
	}

	/**
	 * @test SMWQueryPage::getSearchForm
	 *
	 * @since 1.9
	 */
	public function testGetSearchForm() {

		$search = $this->getRandomString();
		$result = $this->getInstance()->getSearchForm( $search );

		$matcher = array(
			'tag' => 'form',
			'descendant' => array( 'tag' => 'input', 'attributes' => array( 'name' => 'property', 'value' => $search ) )
		);

		$this->assertInternalType( 'string', $result );
		$this->assertTag( $matcher, $result );
	}
}
