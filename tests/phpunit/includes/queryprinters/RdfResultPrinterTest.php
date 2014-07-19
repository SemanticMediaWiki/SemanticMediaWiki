<?php

namespace SMW\Test;

use SMW\RdfResultPrinter;

/**
 * @covers \SMW\RdfResultPrinter
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class RdfResultPrinterTest extends QueryPrinterTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\RdfResultPrinter';
	}

	/**
	 * @return RdfResultPrinter
	 */
	private function getInstance( $parameters = array() ) {
		return $this->setParameters( new RdfResultPrinter( 'rdf' ), $parameters );
	}

	public function testConstructor() {

		$this->assertInstanceOf(
			'\SMW\RdfResultPrinter',
			$this->getInstance()
		);
	}

}
