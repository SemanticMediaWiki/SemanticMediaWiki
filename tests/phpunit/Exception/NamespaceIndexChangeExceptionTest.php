<?php

namespace SMW\Tests\Exception;

use SMW\Exception\NamespaceIndexChangeException;

/**
 * @covers \SMW\Exception\NamespaceIndexChangeException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class NamespaceIndexChangeExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new NamespaceIndexChangeException( 'Foo', 'Bar' );

		$this->assertInstanceof(
			NamespaceIndexChangeException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
