<?php

namespace SMW\Tests\MediaWiki\Hooks;

use SMW\MediaWiki\Hooks\AdminLinks;

/**
 * @covers \SMW\MediaWiki\Hooks\AdminLinks
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class AdminLinksTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			AdminLinks::class,
			new AdminLinks()
		);
	}

}
