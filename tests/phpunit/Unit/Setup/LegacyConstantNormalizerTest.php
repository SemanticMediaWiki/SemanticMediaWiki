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
		// Many tests in this class deliberately invoke normalize() with
		// legacy SMW_* integer constants and verify deprecation via
		// wasDeprecationEmitted(). Suppress the PHP-level user-deprecation
		// output so CI stderr stays clean; the behavioural assertion is
		// unaffected because wasDeprecationEmitted() reads our own static
		// flag, not the error handler.
		//
		// Trade-off: the suppression applies to every test in this class,
		// including ones that don't exercise the deprecation path — so if a
		// future code change accidentally emits E_USER_DEPRECATED from a
		// non-legacy code path in LegacyConstantNormalizer, it would be
		// silently swallowed here. Catching that would require either
		// per-method wrapping (~20 sites) or moving the deprecation-emitting
		// tests to their own subclass. Accepted as-is for now.
		set_error_handler(
			static fn ( int $severity ) => $severity === E_USER_DEPRECATED,
			E_USER_DEPRECATED
		);
	}

	protected function tearDown(): void {
		restore_error_handler();
		parent::tearDown();
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

	/**
	 * @dataProvider provideFlagStringForms
	 */
	public function testFlag_stringForm_normalizesToBitmask( string $key, array $stringForm, int $expected ) {
		$this->assertSame( $expected, LegacyConstantNormalizer::normalize( $key, $stringForm ) );
	}

	/**
	 * @dataProvider provideFlagLegacyForms
	 */
	public function testFlag_legacyBitmask_passesThroughAndDeprecates( string $key, int $legacyBitmask ) {
		$this->assertSame( $legacyBitmask, LegacyConstantNormalizer::normalize( $key, $legacyBitmask ) );
		$this->assertTrue( LegacyConstantNormalizer::wasDeprecationEmitted( $key ) );
	}

	public function provideFlagStringForms(): array {
		return [
			'smwgQFeatures' => [
				'smwgQFeatures',
				[ 'property', 'category', 'concept', 'namespace', 'conjunction', 'disjunction' ],
				SMW_PROPERTY_QUERY | SMW_CATEGORY_QUERY | SMW_CONCEPT_QUERY | SMW_NAMESPACE_QUERY | SMW_CONJUNCTION_QUERY | SMW_DISJUNCTION_QUERY,
			],
			'smwgQConceptFeatures' => [
				'smwgQConceptFeatures',
				[ 'property', 'category', 'concept', 'namespace', 'conjunction', 'disjunction' ],
				SMW_PROPERTY_QUERY | SMW_CATEGORY_QUERY | SMW_CONCEPT_QUERY | SMW_NAMESPACE_QUERY | SMW_CONJUNCTION_QUERY | SMW_DISJUNCTION_QUERY,
			],
			'smwgQSortFeatures' => [
				'smwgQSortFeatures',
				[ 'sort', 'random' ],
				SMW_QSORT | SMW_QSORT_RANDOM,
			],
			'smwgSparqlQFeatures' => [
				'smwgSparqlQFeatures',
				[ 'redirects', 'subproperties', 'subcategories' ],
				SMW_SPARQL_QF_REDI | SMW_SPARQL_QF_SUBP | SMW_SPARQL_QF_SUBC,
			],
			'smwgCategoryFeatures' => [
				'smwgCategoryFeatures',
				[ 'redirect', 'instance', 'hierarchy' ],
				SMW_CAT_REDIRECT | SMW_CAT_INSTANCE | SMW_CAT_HIERARCHY,
			],
			'smwgBrowseFeatures' => [
				'smwgBrowseFeatures',
				[ 'toolbox-link', 'show-incoming', 'show-group', 'use-api' ],
				SMW_BROWSE_TLINK | SMW_BROWSE_SHOW_INCOMING | SMW_BROWSE_SHOW_GROUP | SMW_BROWSE_USE_API,
			],
			'smwgAdminFeatures' => [
				'smwgAdminFeatures',
				[ 'refresh', 'setup', 'disposal', 'pstats', 'fullt', 'maintenance-script-docs', 'show-overview', 'alert-last-optimization-run' ],
				SMW_ADM_REFRESH | SMW_ADM_SETUP | SMW_ADM_DISPOSAL | SMW_ADM_PSTATS | SMW_ADM_FULLT | SMW_ADM_MAINTENANCE_SCRIPT_DOCS | SMW_ADM_SHOW_OVERVIEW | SMW_ADM_ALERT_LAST_OPTIMIZATION_RUN,
			],
			'smwgParserFeatures' => [
				'smwgParserFeatures',
				[ 'strict', 'inline-errors', 'hidden-categories' ],
				SMW_PARSER_STRICT | SMW_PARSER_INL_ERROR | SMW_PARSER_HID_CATS,
			],
			'smwgDVFeatures' => [
				'smwgDVFeatures',
				[ 'provider-redirect', 'monolingual-langcode', 'pattern-validation', 'wpv-display-title', 'time-calendar-model', 'preferred-label', 'provider-link-hint' ],
				SMW_DV_PROV_REDI | SMW_DV_MLTV_LCODE | SMW_DV_PVAP | SMW_DV_WPV_DTITLE | SMW_DV_TIMEV_CM | SMW_DV_PPLB | SMW_DV_PROV_LHNT,
			],
			'smwgFulltextSearchIndexableDataTypes' => [
				'smwgFulltextSearchIndexableDataTypes',
				[ 'blob', 'uri' ],
				SMW_FT_BLOB | SMW_FT_URI,
			],
			'smwgRemoteReqFeatures' => [
				'smwgRemoteReqFeatures',
				[ 'send-response', 'show-note' ],
				SMW_REMOTE_REQ_SEND_RESPONSE | SMW_REMOTE_REQ_SHOW_NOTE,
			],
			'smwgExperimentalFeatures' => [
				'smwgExperimentalFeatures',
				[ 'queryresult-prefetch', 'showparser-curtailment' ],
				SMW_QUERYRESULT_PREFETCH | SMW_SHOWPARSER_USE_CURTAILMENT,
			],
			'smwgFieldTypeFeatures' => [
				'smwgFieldTypeFeatures',
				[ 'char-nocase', 'char-long' ],
				SMW_FIELDT_CHAR_NOCASE | SMW_FIELDT_CHAR_LONG,
			],
			'smwgQueryProfiler' => [
				'smwgQueryProfiler',
				[ 'parameters', 'duration' ],
				SMW_QPRFL_PARAMS | SMW_QPRFL_DUR,
			],
		];
	}

	public function provideFlagLegacyForms(): array {
		return [
			'smwgQFeatures'                        => [ 'smwgQFeatures', SMW_PROPERTY_QUERY | SMW_CATEGORY_QUERY ],
			'smwgQSortFeatures'                    => [ 'smwgQSortFeatures', SMW_QSORT | SMW_QSORT_RANDOM ],
			'smwgSparqlQFeatures'                  => [ 'smwgSparqlQFeatures', SMW_SPARQL_QF_REDI ],
			'smwgCategoryFeatures'                 => [ 'smwgCategoryFeatures', SMW_CAT_REDIRECT | SMW_CAT_INSTANCE ],
			'smwgBrowseFeatures'                   => [ 'smwgBrowseFeatures', SMW_BROWSE_TLINK | SMW_BROWSE_USE_API ],
			'smwgAdminFeatures'                    => [ 'smwgAdminFeatures', SMW_ADM_REFRESH | SMW_ADM_SETUP ],
			'smwgParserFeatures'                   => [ 'smwgParserFeatures', SMW_PARSER_STRICT | SMW_PARSER_INL_ERROR ],
			'smwgDVFeatures'                       => [ 'smwgDVFeatures', SMW_DV_PROV_REDI | SMW_DV_PPLB ],
			'smwgFulltextSearchIndexableDataTypes' => [ 'smwgFulltextSearchIndexableDataTypes', SMW_FT_BLOB | SMW_FT_URI ],
			'smwgRemoteReqFeatures'                => [ 'smwgRemoteReqFeatures', SMW_REMOTE_REQ_SEND_RESPONSE ],
			'smwgExperimentalFeatures'             => [ 'smwgExperimentalFeatures', SMW_QUERYRESULT_PREFETCH ],
			'smwgFieldTypeFeatures'                => [ 'smwgFieldTypeFeatures', SMW_FIELDT_CHAR_NOCASE ],
			'smwgQueryProfiler'                    => [ 'smwgQueryProfiler', SMW_QPRFL_PARAMS | SMW_QPRFL_DUR ],
		];
	}

	public function testFlag_fieldTypeFeatures_falseSentinelIsPreserved() {
		$this->assertFalse(
			LegacyConstantNormalizer::normalize( 'smwgFieldTypeFeatures', false )
		);
	}

	public function testFlag_fieldTypeFeatures_emptyArrayIsNotFalse() {
		// `[]` means "register the component, no flags set" (yields integer 0),
		// while `false` means "skip the component entirely". SetupFile.php's
		// `!== false` check distinguishes them, so the normalizer must too.
		$this->assertSame(
			0,
			LegacyConstantNormalizer::normalize( 'smwgFieldTypeFeatures', [] )
		);
	}

	public function testFlag_otherSettings_falseConvertsToZero() {
		$this->assertSame(
			0,
			LegacyConstantNormalizer::normalize( 'smwgFactboxFeatures', false )
		);
	}

	public function testFlag_queryProfiler_falseSentinelIsPreserved() {
		// AskParserFunction::addQueryProfile does `$settings->get(...) === false`
		// to short-circuit profiling entirely. The sentinel must reach Settings
		// untouched, otherwise admin code that disables profiling silently
		// stops doing so.
		$this->assertFalse(
			LegacyConstantNormalizer::normalize( 'smwgQueryProfiler', false )
		);
		$this->assertFalse( LegacyConstantNormalizer::wasDeprecationEmitted( 'smwgQueryProfiler' ) );
	}

	public function testFlag_queryProfiler_trueIsDeprecatedAliasForEmpty() {
		// `true` was the historical "enabled, no detail fields" default. It
		// maps to bitmask 0 (functionally identical to `[]`) and emits a
		// deprecation notice; admins migrate to `[]` before 8.0.
		$this->assertSame(
			0,
			LegacyConstantNormalizer::normalize( 'smwgQueryProfiler', true )
		);
		$this->assertTrue( LegacyConstantNormalizer::wasDeprecationEmitted( 'smwgQueryProfiler' ) );
	}

	public function testFlag_queryProfiler_emptyArrayIsZeroNotFalse() {
		// `[]` (enabled, no detail flags) and `false` (disabled entirely)
		// are semantically distinct: the `=== false` short-circuit treats
		// the former as "create the profile, no extra fields" but skips
		// profile creation for the latter. The normalizer must keep them
		// distinguishable downstream.
		$this->assertSame(
			0,
			LegacyConstantNormalizer::normalize( 'smwgQueryProfiler', [] )
		);
		$this->assertFalse( LegacyConstantNormalizer::wasDeprecationEmitted( 'smwgQueryProfiler' ) );
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

	public function testGetStringFormForConstant_enumKey_returnsKebab() {
		$this->assertSame(
			'nonempty',
			LegacyConstantNormalizer::getStringFormForConstant( 'smwgShowFactbox', SMW_FACTBOX_NONEMPTY )
		);
		$this->assertSame(
			'all',
			LegacyConstantNormalizer::getStringFormForConstant( 'smwgQConceptCaching', CONCEPT_CACHE_ALL )
		);
	}

	public function testGetStringFormForConstant_flagKey_returnsKebab() {
		$this->assertSame(
			'strict',
			LegacyConstantNormalizer::getStringFormForConstant( 'smwgParserFeatures', SMW_PARSER_STRICT )
		);
		$this->assertSame(
			'links-in-values',
			LegacyConstantNormalizer::getStringFormForConstant( 'smwgParserFeatures', SMW_PARSER_LINV )
		);
	}

	public function testGetStringFormForConstant_unknownKey_returnsNull() {
		$this->assertNull(
			LegacyConstantNormalizer::getStringFormForConstant( 'smwgSomeUnregisteredSetting', 42 )
		);
	}

	public function testGetStringFormForConstant_unmappedIntForKnownKey_returnsNull() {
		// smwgParserFeatures' map doesn't include `SMW_FACTBOX_HIDDEN` (a value
		// from a different setting's map). Reverse-lookup must stay scoped to
		// the supplied key and return null rather than a stray kebab name
		// drawn from another setting that happens to share the integer.
		$this->assertNull(
			LegacyConstantNormalizer::getStringFormForConstant( 'smwgParserFeatures', 99999 )
		);
	}

}
