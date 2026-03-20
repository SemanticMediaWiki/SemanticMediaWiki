<?php

namespace SMW\Tests\Unit\DataValues;

use PHPUnit\Framework\TestCase;
use SMW\DataValues\TelephoneUriValue;
use SMW\DataValues\URIValue;

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
			URIValue::class,
			new TelephoneUriValue()
		);
	}
}
