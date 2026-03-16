<?php

namespace SMW\Tests\Exception;

use PHPUnit\Framework\TestCase;
use SMW\Exception\FileNotReadableException;

/**
 * @covers \SMW\Exception\FileNotReadableException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class FileNotReadableExceptionTest extends TestCase {

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
