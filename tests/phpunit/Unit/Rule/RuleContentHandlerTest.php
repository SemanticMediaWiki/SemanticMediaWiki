<?php

namespace SMW\Tests\Rule;

use SMW\Rule\RuleContentHandler;

/**
 * @covers \SMW\Rule\RuleContentHandler
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RuleContentHandlerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceof(
			'\JsonContentHandler',
			new RuleContentHandler()
		);
	}

}
