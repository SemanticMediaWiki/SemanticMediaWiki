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

	public function testEnum_allKnownStrings_mapToTheirConstants() {
		$this->assertSame( SMW_FACTBOX_HIDDEN, LegacyConstantNormalizer::normalize( 'smwgShowFactbox', 'hidden' ) );
		$this->assertSame( SMW_FACTBOX_SPECIAL, LegacyConstantNormalizer::normalize( 'smwgShowFactbox', 'special' ) );
		$this->assertSame( SMW_FACTBOX_NONEMPTY, LegacyConstantNormalizer::normalize( 'smwgShowFactbox', 'nonempty' ) );
		$this->assertSame( SMW_FACTBOX_SHOWN, LegacyConstantNormalizer::normalize( 'smwgShowFactbox', 'shown' ) );
	}

	/**
	 * @dataProvider provideEnumStringForms
	 */
	public function testEnum_stringForm_normalizesToConstant( string $key, string $stringForm, mixed $expected ) {
		$this->assertSame( $expected, LegacyConstantNormalizer::normalize( $key, $stringForm ) );
	}

	/**
	 * @dataProvider provideEnumLegacyForms
	 */
	public function testEnum_legacyForm_passesThroughAndDeprecates( string $key, mixed $legacyValue ) {
		$this->assertSame( $legacyValue, LegacyConstantNormalizer::normalize( $key, $legacyValue ) );
		$this->assertTrue( LegacyConstantNormalizer::wasDeprecationEmitted( $key ) );
	}

	public function provideEnumStringForms(): array {
		return [
			'smwgShowFactboxEdit/nonempty'            => [ 'smwgShowFactboxEdit', 'nonempty', SMW_FACTBOX_NONEMPTY ],
			'smwgShowFactboxEdit/shown'               => [ 'smwgShowFactboxEdit', 'shown', SMW_FACTBOX_SHOWN ],
			'smwgQEqualitySupport/none'               => [ 'smwgQEqualitySupport', 'none', SMW_EQ_NONE ],
			'smwgQEqualitySupport/some'               => [ 'smwgQEqualitySupport', 'some', SMW_EQ_SOME ],
			'smwgQEqualitySupport/full'               => [ 'smwgQEqualitySupport', 'full', SMW_EQ_FULL ],
			'smwgQConceptCaching/none'                => [ 'smwgQConceptCaching', 'none', CONCEPT_CACHE_NONE ],
			'smwgQConceptCaching/hard'                => [ 'smwgQConceptCaching', 'hard', CONCEPT_CACHE_HARD ],
			'smwgQConceptCaching/all'                 => [ 'smwgQConceptCaching', 'all', CONCEPT_CACHE_ALL ],
			'smwgSparqlRepositoryFeatures/none'       => [ 'smwgSparqlRepositoryFeatures', 'none', SMW_SPARQL_NONE ],
			'smwgSparqlRepositoryFeatures/ping'       => [ 'smwgSparqlRepositoryFeatures', 'connection-ping', SMW_SPARQL_CONNECTION_PING ],
			'smwgResultFormatsFeatures/none'          => [ 'smwgResultFormatsFeatures', 'none', SMW_RF_NONE ],
			'smwgResultFormatsFeatures/template'      => [ 'smwgResultFormatsFeatures', 'template-outsep', SMW_RF_TEMPLATE_OUTSEP ],
		];
	}

	public function provideEnumLegacyForms(): array {
		return [
			'smwgShowFactboxEdit'          => [ 'smwgShowFactboxEdit', SMW_FACTBOX_NONEMPTY ],
			'smwgQEqualitySupport'         => [ 'smwgQEqualitySupport', SMW_EQ_SOME ],
			'smwgQConceptCaching'          => [ 'smwgQConceptCaching', CONCEPT_CACHE_HARD ],
			'smwgSparqlRepositoryFeatures' => [ 'smwgSparqlRepositoryFeatures', SMW_SPARQL_CONNECTION_PING ],
			'smwgResultFormatsFeatures'    => [ 'smwgResultFormatsFeatures', SMW_RF_TEMPLATE_OUTSEP ],
		];
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

	public function testNonRegisteredKey_legacyShapedIntDoesNotDeprecate() {
		// Passing what looks like a legacy SMW_* integer to a key the normalizer
		// doesn't know about must NOT fire a deprecation; the value falls through
		// untouched and downstream behaviour is whatever it was before this PR.
		LegacyConstantNormalizer::normalize( 'smwgUnknownSetting', SMW_FACTBOX_NONEMPTY );
		$this->assertFalse( LegacyConstantNormalizer::wasDeprecationEmitted( 'smwgUnknownSetting' ) );
	}

	public function testDeprecation_keysAreSuppressedIndependently() {
		// Deprecating one setting must not suppress notices for any other
		// registered setting. This invariant is the foundation that PRs B and C
		// extend across all 22 remaining settings; if it ever regressed, the
		// first legacy-form setting in a wiki's LocalSettings.php would silence
		// the rest.
		LegacyConstantNormalizer::normalize( 'smwgShowFactbox', SMW_FACTBOX_NONEMPTY );
		$this->assertTrue( LegacyConstantNormalizer::wasDeprecationEmitted( 'smwgShowFactbox' ) );
		$this->assertFalse( LegacyConstantNormalizer::wasDeprecationEmitted( 'smwgFactboxFeatures' ) );

		LegacyConstantNormalizer::normalize( 'smwgFactboxFeatures', SMW_FACTBOX_CACHE );
		$this->assertTrue( LegacyConstantNormalizer::wasDeprecationEmitted( 'smwgShowFactbox' ) );
		$this->assertTrue( LegacyConstantNormalizer::wasDeprecationEmitted( 'smwgFactboxFeatures' ) );
	}

}
