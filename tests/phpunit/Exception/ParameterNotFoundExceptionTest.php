<?php

namespace SMW\Tests\Exception;

use SMW\Exception\ParameterNotFoundException;

/**
 * @covers \SMW\Exception\ParameterNotFoundException
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ParameterNotFoundExceptionTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new ParameterNotFoundException( 'foo' );

		$this->assertInstanceof(
			ParameterNotFoundException::class,
			$instance
		);

		$this->assertInstanceof(
			'\InvalidArgumentException',
			$instance
		);
	}

	public function testGetName() {

		$instance = new ParameterNotFoundException( 'bar' );

		$this->assertEquals(
			'bar',
			$instance->getName()
		);
	}

}
