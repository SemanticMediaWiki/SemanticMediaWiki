<?php

namespace SMW\Tests;

use SMW\ProcessLruCache;
use SMW\StringCondition;

/**
 * @covers \SMW\ProcessLruCache
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ProcessLruCacheTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			ProcessLruCache::class,
			new ProcessLruCache( [] )
		);
	}

	public function testConstructWithConfig() {

		$instance = ProcessLruCache::newFromConfig(
			[
				'Foo' => 100
			]
		);

		$this->assertInstanceOf(
			'Onoi\Cache\Cache',
			$instance->get( 'Foo' )
		);

		$instance->reset();
	}

	public function testGetOnUnknownCacheThrowsException() {

		$instance = new ProcessLruCache( [] );

		$this->setExpectedException( '\RuntimeException' );
		$instance->get( 'Foo' );
	}

}
