<?php

namespace SMW\Tests\Exception;

use SMW\Exception\FileNotWritableException;

/**
 * @covers \SMW\Exception\FileNotWritableException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FileNotWritableExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new FileNotWritableException( 'Foo' );

		$this->assertInstanceof(
			FileNotWritableException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
