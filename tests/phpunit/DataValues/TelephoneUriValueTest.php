<?php

namespace SMW\Tests\DataValues;

use PHPUnit\Framework\TestCase;
use SMW\DataValues\TelephoneUriValue;

/**
 * @covers \SMW\DataValues\TelephoneUriValue
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class TelephoneUriValueTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			TelephoneUriValue::class,
			new TelephoneUriValue()
		);

		$this->assertInstanceOf(
			'\SMWURIValue',
			new TelephoneUriValue()
		);
	}
}
