<?php

namespace SMW\Tests\Rule;

use SMW\Rule\RuleContent;

/**
 * @covers \SMW\Rule\RuleContent
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RuleContentTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceof(
			'\JsonContent',
			new RuleContent( 'foo' )
		);
	}

}
