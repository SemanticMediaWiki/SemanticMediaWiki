<?php

namespace SMW\Tests\Rule;

use SMW\Rule\Rule;

/**
 * @covers \SMW\Rule\Rule
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class RuleTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$instance = new Rule( 'foo', [], [] );

		$this->assertInstanceof(
			Rule::class,
			$instance
		);
	}

}
