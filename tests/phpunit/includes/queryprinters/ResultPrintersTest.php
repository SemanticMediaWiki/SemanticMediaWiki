<?php

namespace SMW\Test;

use ParamProcessor\ParamDefinition;
use SMW\ResultPrinter;
use SMWQueryProcessor;

/**
 * Does some basic tests for the SMW\ResultPrinter deriving classes
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

		$formats = [];

		foreach ( $smwgResultFormats as $format => $class ) {
			$formats[] = [ $format, $class, true ];
			$formats[] = [ $format, $class, false ];
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

		$instances = [];

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
		$params = $printer->getParamDefinitions( SMWQueryProcessor::getParameters( null, $printer ) );

		$params = ParamDefinition::getCleanDefinitions( $params );

		$this->assertInternalType( 'array', $params );
	}

}
