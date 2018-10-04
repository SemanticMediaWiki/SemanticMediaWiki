<?php

namespace SMW\Tests\Schema\Content;

use SMW\Schema\Content\ContentHandler;

/**
 * @covers \SMW\Schema\Content\ContentHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ContentHandlerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceof(
			'\JsonContentHandler',
			new ContentHandler()
		);
	}

}
