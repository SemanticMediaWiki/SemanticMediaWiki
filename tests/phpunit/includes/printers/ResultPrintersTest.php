<?php

namespace SMW\Tests;

/**
 * Does some basic tests for the SMW\ResultPrinter deriving classes.
 *
 * @file
 * @since 1.8
 *
 * @ingroup SMW
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group ResultPrinters
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ResultPrintersTest extends \MediaWikiTestCase {

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

		return $instances;
	}

	/**
	 * @dataProvider instanceProvider
	 *
	 * @param \SMWResultPrinter $printer
	 */
	public function testGetParamDefinitions( \SMWResultPrinter $printer ) {
		$params = $printer->getParamDefinitions( \SMWQueryProcessor::getParameters() );

		$params = \ParamDefinition::getCleanDefinitions( $params );

		$this->assertInternalType( 'array', $params );
	}

}
