<?php

namespace SMW\Tests;

use SMW\Query\ResultPrinters\ResultPrinter;

/**
 * @group SMW
 * @group SMWExtension
 *
 * @codeCoverageIgnore
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class QueryPrinterRegistryTestCase extends QueryPrinterTestCase {

	/**
	 * Returns the names of the formats supported by the
	 * ResultPrinter being tested.
	 *
	 * @since 1.8
	 *
	 * @return array
	 */
	abstract public function getFormats();

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
	 * @param bool $isInline
	 *
	 * @return ResultPrinter
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
			static function ( array $args ) use ( $phpFails ) {
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
	 * @param bool $isInline
	 */
	public function testConstructor( $format, $isInline ) {
		$instance = $this->newInstance( $format, $isInline );

		$this->assertInstanceOf( ResultPrinter::class, $instance );
	}
}
