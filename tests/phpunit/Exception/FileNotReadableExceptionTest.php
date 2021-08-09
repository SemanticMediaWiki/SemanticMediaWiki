<?php

namespace SMW\Tests\Exception;

use SMW\Exception\FileNotReadableException;

/**
 * @covers \SMW\Exception\FileNotReadableException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FileNotReadableExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new FileNotReadableException( 'Foo' );

		$this->assertInstanceof(
			FileNotReadableException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
