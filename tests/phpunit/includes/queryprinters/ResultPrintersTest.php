<?php

namespace SMW\Test;

use SMW\ResultPrinter;
use SMWQueryProcessor;

/**
 * Does some basic tests for the SMW\ResultPrinter deriving classes
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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */

/**
 * @covers \SMW\ResultPrinter
 *
 * @ingroup QueryPrinterTest
 *
 * @group SMW
 * @group SMWExtension
 */
class ResultPrintersTest extends QueryPrinterTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return false;
	}

	public function constructorProvider() {
		global $smwgResultFormats;

		$formats = array();

		foreach ( $smwgResultFormats as $format => $class ) {
			$formats[] = array( $format, $class, true );
			$formats[] = array( $format, $class, false );
		}

		return $formats;
	}

	/**
	 * @dataProvider constructorProvider
	 *
	 * @param string $format
	 * @param string $class
	 * @param boolean $isInline
	 */
	public function testConstructor( $format, $class, $isInline ) {
		$instance = new $class( $format, $isInline );
		$this->assertInstanceOf( '\SMWIResultPrinter', $instance );
	}

	public function instanceProvider() {
		global $smwgResultFormats;

		$instances = array();

		foreach ( $smwgResultFormats as $format => $class ) {
			$instances[] = new $class( $format, true );
		}

		return $this->arrayWrap( $instances );
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \SMWResultPrinter $printer
	 */
	public function testGetParamDefinitions( ResultPrinter $printer ) {
		$params = $printer->getParamDefinitions( SMWQueryProcessor::getParameters() );

		$params = \ParamDefinition::getCleanDefinitions( $params );

		$this->assertInternalType( 'array', $params );
	}

}
