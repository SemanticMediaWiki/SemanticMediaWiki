<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\HookHandler;

/**
 * @covers \SMW\MediaWiki\Hooks\HookHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HookHandlerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			HookHandler::class,
			new HookHandler()
		);
	}

	public function testOptions() {

		$instance = new HookHandler();

		$this->assertNull(
			$instance->getOption( 'Foo' )
		);

		$this->assertFalse(
			$instance->getOption( 'Foo', false )
		);

		$instance->setOptions( [ 'Foo' => 42 ] );

		$this->assertEquals(
			42,
			$instance->getOption( 'Foo' )
		);

		$this->assertTrue(
			$instance->isFlagSet( 'Foo', 42 )
		);
	}

}
