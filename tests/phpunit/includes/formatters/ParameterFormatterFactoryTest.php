<?php

namespace SMW\Test;

use SMW\ParameterFormatterFactory;

/**
 * Tests for the ParameterFormatterFactory class
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
 * @covers \SMW\ParameterFormatterFactory
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class ParameterFormatterFactoryTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\ParameterFormatterFactory';
	}

	/**
	 * Helper method that returns a ArrayFormatter object
	 *
	 * @since 1.9
	 *
	 * @param array $params
	 *
	 * @return ArrayFormatter
	 */
	private function getInstance( array $params = array() ) {
		return ParameterFormatterFactory::newFromArray( $params );
	}

	/**
	 * @test ParameterFormatterFactory::newFromArray
	 *
	 * @since 1.9
	 */
	public function testNewFromArray() {
		$instance = $this->getInstance();
		$this->assertInstanceOf( '\SMW\ArrayFormatter', $instance );
	}
}
