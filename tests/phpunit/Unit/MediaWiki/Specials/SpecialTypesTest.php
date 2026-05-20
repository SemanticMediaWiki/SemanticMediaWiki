<?php

namespace SMW\Tests\Unit\MediaWiki\Specials;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\SpecialTypes;
use SMW\Settings;
use SMW\Store;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialTypes
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class SpecialTypesTest extends TestCase {

	public function testCanConstruct() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();
		$settings = $this->createMock( Settings::class );

		$this->assertInstanceOf(
			SpecialTypes::class,
			new SpecialTypes( $store, $settings )
		);
	}

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
