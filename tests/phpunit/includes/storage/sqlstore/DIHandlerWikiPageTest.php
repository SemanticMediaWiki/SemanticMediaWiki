<?php

namespace SMW\Test;

use SMW\InvalidPredefinedPropertyException;
use SMW\StoreFactory;

use SMWDIHandlerWikiPage;
use SMWDIProperty;

/**
 * Tests for the SMWDIHandlerWikiPage class
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
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * Tests for the SMWDIHandlerWikiPage class
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class DIHandlerWikiPageTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string
	 */
	public function getClass() {
		return 'SMWDIHandlerWikiPage';
	}

	/**
	 * Helper method that returns a SMWDIHandlerWikiPage object
	 *
	 * @since 1.9
	 *
	 * @return SMWDIHandlerWikiPage
	 */
	private function getInstance() {
		return new SMWDIHandlerWikiPage( StoreFactory::getStore() );
	}

	/**
	 * @test SMWDIHandlerWikiPage::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->getInstance() );
	}

	/**
	 * @test SMWDIHandlerWikiPage::dataItemFromDBKeys
	 *
	 * @since 1.9
	 *
	 * @throws SMWDataItemException
	 */
	public function testDataItemFromDBKeysException() {

		$this->setExpectedException( 'SMWDataItemException' );

		$instance = $this->getInstance();
		$result = $instance->dataItemFromDBKeys( array() );

		$this->assertInstanceOf( 'SMWDIWikiPage', $result );

	}

	/**
	 * @test SMWDIHandlerWikiPage::dataItemFromDBKeys
	 * @dataProvider getDBKeys
	 *
	 * @see bug 48711
	 *
	 * @since 1.9
	 *
	 * @param $dbKeys
	 * @param $expected
	 *
	 * @throws InvalidPredefinedPropertyException
	 */
	public function testDataItemFromDBKeys( $dbKeys, $expected ) {

		$instance = $this->getInstance();

		try {
			$result = $instance->dataItemFromDBKeys( $dbKeys );
			$this->assertInstanceOf( $expected, $result );
			return;
		} catch ( InvalidPredefinedPropertyException $e ) {
			$this->assertEquals( $expected, 'InvalidPredefinedPropertyException' );
			return;
		}

		$this->fail( 'An expected exception has not been raised.' );
	}

	/**
	 * Provides dbKeys sample
	 *
	 * @return array
	 */
	public function getDBKeys() {
		return array(

			// #0 SMW_NS_PROPERTY, user defined property
			array(
				array( 'Foo', SMW_NS_PROPERTY, 'bar', '', '' ), 'SMWDIWikiPage'
			),

			// #1 SMW_NS_PROPERTY, pre-defined property
			array(
				array( '_Foo', SMW_NS_PROPERTY, 'bar', '', '' ), 'SMWDIWikiPage'
			),

			// #2 SMW_NS_PROPERTY, pre-defined property (see bug 48711)
			array(
				array( '_Foo', SMW_NS_PROPERTY, '', '', '' ), 'InvalidPredefinedPropertyException'
			),
		);
	}
}
