<?php

namespace SMW\Tests\Utils;

use SMW\Utils\UrlArgs;

/**
 * @covers \SMW\Utils\UrlArgs
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class UrlArgsTest extends \PHPUnit_Framework_TestCase {

	public function testToString() {

		$instance = new UrlArgs();

		$instance->set( 'foo', 42 );
		$instance->set( 'bar', 1001 );
		$instance->setFragment( 'foobar' );

		$this->assertContains(
			'foo=42&bar=1001#foobar',
			$instance->__toString()
		);
	}

	public function testGet() {

		$instance = new UrlArgs();

		$instance->set( 'foo', 42 );

		$this->assertEquals(
			42,
			$instance->get( 'foo' )
		);

		$this->assertEquals(
			null,
			$instance->get( 42 )
		);
	}

	public function testDelete() {

		$instance = new UrlArgs();

		$instance->set( 'foo', 42 );

		$this->assertEquals(
			42,
			$instance->get( 'foo' )
		);

		$instance->delete( 'foo' );

		$this->assertEquals(
			null,
			$instance->get( 'foo' )
		);
	}

}
