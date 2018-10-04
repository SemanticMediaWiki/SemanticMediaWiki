<?php

namespace SMW\Test;

use SMW\RawResultPrinter;

/**
 * @covers \SMW\RawResultPrinter
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */
class RawResultPrinterTest extends QueryPrinterTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\RawResultPrinter';
	}

	/**
	 * @return RawResultPrinter
	 */
	private function getInstance( $parameters = [] ) {

		$instance = $this->getMockBuilder( '\SMW\RawResultPrinter' )
			->disableOriginalConstructor()
			->setConstructorArgs( [ 'api' ] )
			->getMockForAbstractClass();

		return $this->setParameters( $instance, $parameters );
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\RawResultPrinter',
			$this->getInstance()
		);
	}

}
