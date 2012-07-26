<?php

namespace SMW\Tests;

/**
 * Base class for SMW\ResultPrinter tests.
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
abstract class ResultPrinterTest extends \MediaWikiTestCase {

	/**
	 * Returns the names of the formats supported by the \SMW\ResultPrinter being tested.
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	public abstract function getFormats();

	/**
	 * Returns the name of the \SMW\ResultPrinter deriving class this test tests.
	 *
	 * @since 1.8
	 *
	 * @return string
	 */
	public abstract function getClass();

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
	 * @return \SMWResultPrinter
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

		$this->assertInstanceOf( '\SMWResultPrinter', $instance );
	}

}
