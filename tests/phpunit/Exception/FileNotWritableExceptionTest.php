<?php

namespace SMW\Tests\Exception;

use SMW\Exception\FileNotWritableException;

/**
 * @covers \SMW\Exception\FileNotWritableException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class FileNotWritableExceptionTest extends \PHPUnit\Framework\TestCase {

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
