<?php

namespace SMW\Tests\Utils;

use SMW\Utils\UrlArgs;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Utils\UrlArgs
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class UrlArgsTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

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

		$this->assertNull(
						$instance->get( 42 )
		);

		$this->assertFalse(
						$instance->get( 42, false )
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

		$this->assertNull(
						$instance->get( 'foo' )
		);
	}

	public function testGetInt() {
		$instance = new UrlArgs(
			[
				'Foo' => '42'
			]
		);

		$this->assertEquals(
			42,
			$instance->getInt( 'Foo' )
		);

		$this->assertNull(
			$instance->getInt( 'NotAvailableReturnNull' )
		);

		$this->assertEquals(
			1001,
			$instance->getInt( 'NotAvailableReturnDefault', 1001 )
		);
	}

	public function testGetArray() {
		$instance = new UrlArgs(
			[
				'Foo' => '42'
			]
		);

		$this->assertEquals(
			[ '42' ],
			$instance->getArray( 'Foo' )
		);

		$this->assertEquals(
			[],
			$instance->getArray( 'NotAvailableReturnEmptyArray' )
		);
	}

	public function testClone() {
		$instance = new UrlArgs(
			[
				'Foo' => '42'
			]
		);

		$this->assertNotSame(
			$instance,
			$instance->clone()
		);
	}

}
