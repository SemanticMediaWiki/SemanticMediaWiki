<?php

namespace SMW\Test;

use SMW\EmbeddedResultPrinter;

/**
 * @covers \SMW\EmbeddedResultPrinter
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class EmbeddedResultPrinterTest extends QueryPrinterTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\EmbeddedResultPrinter';
	}

	/**
	 * @return SMWEmbeddedResultPrinter
	 */
	private function getInstance( $parameters = array() ) {
		return $this->setParameters( new EmbeddedResultPrinter( 'embedded' ), $parameters );
	}

	public function testcanConstruct() {

		$this->assertInstanceOf(
			'\SMW\EmbeddedResultPrinter',
			$this->getInstance()
		);
	}

}
