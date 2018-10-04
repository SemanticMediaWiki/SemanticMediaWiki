<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\HookListener;

/**
 * @covers \SMW\MediaWiki\Hooks\HookListener
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class HooksTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			HookListener::class,
			new HookListener()
		);
	}

}
