<?php

namespace SMW\Tests\Exception;

use SMW\Exception\ConfigPreloadFileNotReadableException;

/**
 * @covers \SMW\Exception\ConfigPreloadFileNotReadableException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ConfigPreloadFileNotReadableExceptionTest extends \PHPUnit_Framework_TestCase {

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
