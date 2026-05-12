<?php

namespace SMW\Tests\Unit\Setup;

use PHPUnit\Framework\TestCase;
use SMW\Setup\LegacyConstantNormalizer;

/**
 * @covers \SMW\Setup\LegacyConstantNormalizer
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class LegacyConstantNormalizerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		LegacyConstantNormalizer::resetDeprecationState();
	}

	public function testEnum_newStringForm_normalizesToConstant() {
		$this->assertSame(
			SMW_FACTBOX_NONEMPTY,
			LegacyConstantNormalizer::normalize( 'smwgShowFactbox', 'nonempty' )
		);
	}

	public function testEnum_allKnownStrings_mapToTheirConstants() {
		$this->assertSame( SMW_FACTBOX_HIDDEN, LegacyConstantNormalizer::normalize( 'smwgShowFactbox', 'hidden' ) );
		$this->assertSame( SMW_FACTBOX_SPECIAL, LegacyConstantNormalizer::normalize( 'smwgShowFactbox', 'special' ) );
		$this->assertSame( SMW_FACTBOX_NONEMPTY, LegacyConstantNormalizer::normalize( 'smwgShowFactbox', 'nonempty' ) );
		$this->assertSame( SMW_FACTBOX_SHOWN, LegacyConstantNormalizer::normalize( 'smwgShowFactbox', 'shown' ) );
	}

	public function testEnum_legacyConstantForm_passesThroughAndDeprecates() {
		$this->assertSame(
			SMW_FACTBOX_NONEMPTY,
			LegacyConstantNormalizer::normalize( 'smwgShowFactbox', SMW_FACTBOX_NONEMPTY )
		);
		$this->assertTrue( LegacyConstantNormalizer::wasDeprecationEmitted( 'smwgShowFactbox' ) );
	}

	public function testEnum_unknownString_emitsWarningAndFallsBackToDefault() {
		$value = LegacyConstantNormalizer::normalize( 'smwgShowFactbox', 'bogus' );
		$this->assertSame( SMW_FACTBOX_HIDDEN, $value );
	}

	public function testFlag_newArrayForm_normalizesToBitmask() {
		$this->assertSame(
			SMW_FACTBOX_CACHE | SMW_FACTBOX_PURGE_REFRESH,
			LegacyConstantNormalizer::normalize( 'smwgFactboxFeatures', [ 'cache', 'purge-refresh' ] )
		);
	}

	public function testFlag_allKnownFlagsCombined() {
		$this->assertSame(
			SMW_FACTBOX_CACHE | SMW_FACTBOX_PURGE_REFRESH | SMW_FACTBOX_DISPLAY_SUBOBJECT | SMW_FACTBOX_DISPLAY_ATTACHMENT,
			LegacyConstantNormalizer::normalize(
				'smwgFactboxFeatures',
				[ 'cache', 'purge-refresh', 'display-subobject', 'display-attachment' ]
			)
		);
	}

	public function testFlag_legacyBitmaskForm_passesThroughAndDeprecates() {
		$bitmask = SMW_FACTBOX_CACHE | SMW_FACTBOX_PURGE_REFRESH;
		$this->assertSame(
			$bitmask,
			LegacyConstantNormalizer::normalize( 'smwgFactboxFeatures', $bitmask )
		);
		$this->assertTrue( LegacyConstantNormalizer::wasDeprecationEmitted( 'smwgFactboxFeatures' ) );
	}

	public function testFlag_mixedForm_normalizesEachElement() {
		$this->assertSame(
			SMW_FACTBOX_CACHE | SMW_FACTBOX_PURGE_REFRESH,
			LegacyConstantNormalizer::normalize(
				'smwgFactboxFeatures',
				[ SMW_FACTBOX_CACHE, 'purge-refresh' ]
			)
		);
		$this->assertTrue( LegacyConstantNormalizer::wasDeprecationEmitted( 'smwgFactboxFeatures' ) );
	}

	public function testFlag_unknownStringIsDropped() {
		$value = LegacyConstantNormalizer::normalize( 'smwgFactboxFeatures', [ 'cache', 'bogus' ] );
		$this->assertSame( SMW_FACTBOX_CACHE, $value );
	}

	public function testFlag_scalarStringAutoWraps() {
		$this->assertSame(
			SMW_FACTBOX_CACHE,
			LegacyConstantNormalizer::normalize( 'smwgFactboxFeatures', 'cache' )
		);
	}

	public function testFlag_emptyArray_returnsZero() {
		$this->assertSame( 0, LegacyConstantNormalizer::normalize( 'smwgFactboxFeatures', [] ) );
	}

	public function testFlag_zeroInteger_passesThroughWithoutDeprecation() {
		// `$smwgFactboxFeatures = 0;` is the documented "no flags" form and the
		// natural value-equivalent of `[]`; it must NOT trigger the legacy
		// SMW_FACTBOX_* deprecation. Guard the carve-out in normalizeFlags.
		$this->assertSame( 0, LegacyConstantNormalizer::normalize( 'smwgFactboxFeatures', 0 ) );
		$this->assertFalse( LegacyConstantNormalizer::wasDeprecationEmitted( 'smwgFactboxFeatures' ) );
	}

	public function testDeprecation_firesOnlyOncePerSettingPerRequest() {
		LegacyConstantNormalizer::normalize( 'smwgShowFactbox', SMW_FACTBOX_NONEMPTY );
		$this->assertTrue( LegacyConstantNormalizer::wasDeprecationEmitted( 'smwgShowFactbox' ) );

		LegacyConstantNormalizer::normalize( 'smwgShowFactbox', SMW_FACTBOX_HIDDEN );
		$this->assertTrue( LegacyConstantNormalizer::wasDeprecationEmitted( 'smwgShowFactbox' ) );

		LegacyConstantNormalizer::resetDeprecationState();
		$this->assertFalse( LegacyConstantNormalizer::wasDeprecationEmitted( 'smwgShowFactbox' ) );
	}

	public function testDeprecation_newFormDoesNotEmit() {
		LegacyConstantNormalizer::normalize( 'smwgShowFactbox', 'nonempty' );
		$this->assertFalse( LegacyConstantNormalizer::wasDeprecationEmitted( 'smwgShowFactbox' ) );

		LegacyConstantNormalizer::normalize( 'smwgFactboxFeatures', [ 'cache' ] );
		$this->assertFalse( LegacyConstantNormalizer::wasDeprecationEmitted( 'smwgFactboxFeatures' ) );
	}

	public function testNonRegisteredKey_passesThrough() {
		$this->assertSame(
			42,
			LegacyConstantNormalizer::normalize( 'smwgUnknownSetting', 42 )
		);
		$this->assertSame(
			'whatever',
			LegacyConstantNormalizer::normalize( 'smwgUnknownSetting', 'whatever' )
		);
	}

	public function testNullValue_passesThrough() {
		$this->assertNull(
			LegacyConstantNormalizer::normalize( 'smwgShowFactbox', null )
		);
	}
}
