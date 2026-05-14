<?php

namespace SMW\Tests\Unit\MediaWiki\Specials;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\SpecialTypes;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialTypes
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class SpecialTypesTest extends TestCase {

	/**
	 * @dataProvider cursorModeProvider
	 */
	public function testShouldUseCursorMode( ?string $offsetParamValue, bool $expected ): void {
		$this->assertSame(
			$expected,
			SpecialTypes::shouldUseCursorMode( $offsetParamValue )
		);
	}

	public static function cursorModeProvider(): array {
		return [
			'no offset param at all' => [ null, true ],
			'explicit offset=0' => [ '0', false ],
			'explicit offset=5' => [ '5', false ],
			'empty offset value' => [ '', false ],
			'negative offset' => [ '-1', false ],
			'non-numeric garbage' => [ 'garbage', false ],
		];
	}

}
