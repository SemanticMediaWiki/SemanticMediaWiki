<?php

namespace SMW\Tests\DataValues;

use SMW\DataValues\TelephoneUriValue;

/**
 * @covers \SMW\DataValues\TelephoneUriValue
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class TelephoneUriValueTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\DataValues\TelephoneUriValue',
			new TelephoneUriValue()
		);

		$this->assertInstanceOf(
			'\SMWURIValue',
			new TelephoneUriValue()
		);
	}
}
