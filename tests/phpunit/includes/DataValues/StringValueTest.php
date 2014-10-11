<?php

namespace SMW\Tests\DataValues;

use SMWStringValue as StringValue;

/**
 * @covers \SMWStringValue
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class StringValueTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMWStringValue',
			new StringValue( '_txt' )
		);
	}

	public function testGetWikiValueForLengthOf() {

		$instance = new StringValue( '_txt' );
		$instance->setUserValue( 'abcdefあいうエオ' );

		$this->assertEquals(
			'abcdefあい',
			$instance->getWikiValueForLengthOf( 8 )
		);

		$this->assertEquals(
			'abcdefあいうエオ',
			$instance->getWikiValueForLengthOf( 14 )
		);
	}

}
