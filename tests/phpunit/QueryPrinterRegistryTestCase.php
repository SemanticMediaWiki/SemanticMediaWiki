<?php

namespace SMW\Test;

/**
 * Base class for SMW\ResultPrinter tests.
 *
 * @group SMW
 * @group SMWExtension
 *
 * @codeCoverageIgnore
 *
 * @license GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class QueryPrinterRegistryTestCase extends QueryPrinterTestCase {

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
		$argumentLists = [];

		foreach ( $this->getFormats() as $format ) {
			$argumentLists[] = [ $format, true ];
			$argumentLists[] = [ $format, false ];
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
	 * @return array
	 */
	public function instanceProvider() {
		$phpFails = [ $this, 'newInstance' ];

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

/**
 * SMWResultPrinter
 *
 * @deprecated since SMW 1.9
 */
class_alias( 'SMW\Test\QueryPrinterRegistryTestCase', 'SMW\Test\ResultPrinterTestCase' );
