<?php

namespace SMW\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use SMW\Exception\ConfigPreloadFileNotReadableException;

/**
 * @covers \SMW\Exception\ConfigPreloadFileNotReadableException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class ConfigPreloadFileNotReadableExceptionTest extends TestCase {

	public function testCanConstruct() {
		$instance = new ConfigPreloadFileNotReadableException( 'Foo' );

		$this->assertInstanceof(
			ConfigPreloadFileNotReadableException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
