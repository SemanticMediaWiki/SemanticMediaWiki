<?php

namespace SMW\Tests\Unit\MediaWiki\Hooks;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Hooks\AdminLinks;

/**
 * @covers \SMW\MediaWiki\Hooks\AdminLinks
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class AdminLinksTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			AdminLinks::class,
			new AdminLinks()
		);
	}

}
