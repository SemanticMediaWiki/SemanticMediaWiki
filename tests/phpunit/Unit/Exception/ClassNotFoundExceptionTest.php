<?php

namespace SMW\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use SMW\Exception\ClassNotFoundException;

/**
 * @covers \SMW\Exception\ClassNotFoundException
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ClassNotFoundExceptionTest extends TestCase {

	public function testCanConstruct() {
		$instance = new ClassNotFoundException( 'Foo' );

		$this->assertInstanceof(
			ClassNotFoundException::class,
			$instance
		);

		$this->assertInstanceof(
			'\RuntimeException',
			$instance
		);
	}

}
