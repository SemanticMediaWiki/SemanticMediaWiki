<?php

namespace SMW\Tests\MediaWiki\Content;

use SMW\MediaWiki\Content\SchemaContentHandler;

/**
 * @covers \SMW\MediaWiki\Content\SchemaContentHandler
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
			new SchemaContentHandler()
		);
	}

}
