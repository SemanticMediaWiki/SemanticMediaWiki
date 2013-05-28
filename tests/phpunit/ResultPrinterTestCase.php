<?php

namespace SMW\Test;

/**
 * Base class for SMW\ResultPrinter tests.
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
 * @since 1.8
 *
 * @file
 * @ingroup Test
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

/**
 * Base class for SMW\ResultPrinter tests
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group ResultPrinters
 */
abstract class ResultPrinterTestCase extends SemanticMediaWikiTestCase {

	/**
	 * Returns the names of the formats supported by the
	 * \SMW\ResultPrinter being tested.
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	public abstract function getFormats();

	/**
	 * @since 1.8
	 *
	 * @return array
	 */
	public function constructorProvider() {
		$argumentLists = array();

		foreach ( $this->getFormats() as $format ) {
			$argumentLists[] = array( $format, true );
			$argumentLists[] = array( $format, false );
		}

		return $argumentLists;
	}

	/**
	 * Creates and returns a new instance of the result printer.
	 *
	 * @since 1.8
	 *
	 * @param string $format
	 * @param boolean $isInline
	 *
	 * @return \SMW\ResultPrinter
	 */
	protected function newInstance( $format, $isInline ) {
		$class = $this->getClass();
		return new $class( $format, $isInline );
	}

	/**
	 * @since 1.8
	 *
	 * @return array
	 */
	public function instanceProvider() {
		$phpFails = array( $this, 'newInstance' );

		return array_map(
			function( array $args ) use ( $phpFails ) {
				return call_user_func_array( $phpFails, $args );
			},
			$this->constructorProvider()
		);
	}

	/**
	 * @dataProvider constructorProvider
	 *
	 * @since 1.8
	 *
	 * @param string $format
	 * @param boolean $isInline
	 */
	public function testConstructor( $format, $isInline ) {
		$instance = $this->newInstance( $format, $isInline );

		$this->assertInstanceOf( '\SMW\ResultPrinter', $instance );
	}
}
