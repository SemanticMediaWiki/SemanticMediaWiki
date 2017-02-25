<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\TypeList;

/**
 * @covers \SMW\DataValues\TypeList
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class TypeListTest extends \PHPUnit_Framework_TestCase {

	public function testGetList() {

		$this->assertInternalType(
			'array',
			TypeList::getList()
		);
	}

}
