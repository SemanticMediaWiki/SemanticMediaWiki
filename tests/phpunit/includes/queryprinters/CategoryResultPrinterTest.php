<?php

namespace SMW\Test;

use SMW\CategoryResultPrinter;

/**
 * @covers \SMW\CategoryResultPrinter
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class CategoryResultPrinterTest extends QueryPrinterTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\CategoryResultPrinter';
	}

	/**
	 * @return CategoryResultPrinter
	 */
	private function getInstance( $parameters = array() ) {
		return $this->setParameters( new CategoryResultPrinter( 'category' ), $parameters );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\CategoryResultPrinter',
			$this->getInstance()
		);
	}

}
