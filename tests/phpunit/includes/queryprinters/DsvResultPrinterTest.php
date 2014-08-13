<?php

namespace SMW\Test;

use SMW\DsvResultPrinter;

/**
 * @covers \SMW\DsvResultPrinter
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class DsvResultPrinterTest extends QueryPrinterTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\DsvResultPrinter';
	}

	/**
	 * @return DsvResultPrinter
	 */
	private function getInstance( $parameters = array() ) {
		return $this->setParameters( new DsvResultPrinter( 'dsv' ), $parameters );
	}

	public function testCanConstruc() {

		$this->assertInstanceOf(
			'\SMW\DsvResultPrinter',
			$this->getInstance()
		);
	}

}
