<?php

namespace SMW\Setup;

use MediaWiki\Logger\LoggerFactory;

/**
 * Normalize legacy integer-constant config values to the JSON-clean string /
 * array-of-strings form used by `extension.json` defaults, while preserving
 * the internal representation expected by SMW (integer bitmask for flag
 * settings, integer or string for enum settings).
 *
 * Both legacy (integer constants from src/Defines.php) and new (string / array
 * of strings) user values are accepted. The legacy form emits one
 * `wfDeprecatedMsg` per setting per request and is scheduled for removal in
 * SMW 8.0.
 *
 * Boundary contract: normalization happens exactly once, when SMW ingests
 * `$GLOBALS` via {@see \SMW\Settings::loadFromGlobals()}. Callers that build
 * `Settings` through {@see \SMW\Settings::newFromArray()} (chiefly tests) are
 * expected to pass values already in the internal form (integer constants or
 * integer bitmasks). Pass an unmapped string at that boundary and downstream
 * `Options::isFlagSet()` / `=== SMW_FOO` comparisons will silently fail.
 *
 * Adding a new FLAG_MAP or ENUM_MAP entry requires three coordinated edits:
 *   1. Add the map entry to this class.
 *   2. Wire the corresponding key into `Settings::loadFromGlobals()` via
 *      `LegacyConstantNormalizer::normalize( $key, $GLOBALS[$key] )`; an
 *      un-wrapped read at that boundary leaves the new form silently
 *      uninterpreted, since downstream callers expect the internal
 *      representation (integer bitmask or integer constant).
 *   3. Cover the key in `SettingsTest::provideFlagBoundaryDualAcceptCases`
 *      (flags) or `provideEnumBoundaryDualAcceptCases` (enums) so the
 *      integration boundary is exercised; the unit tests on this class
 *      run `normalize()` in isolation and cannot catch a missing wrap.
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class LegacyConstantNormalizer {

	/**
	 * Per-request set of setting keys for which a deprecation notice has
	 * already been emitted. Reset between requests; resetDeprecationState()
	 * is exposed for test isolation.
	 *
	 * @var array<string, true>
	 */
	private static array $deprecationEmitted = [];

	/**
	 * Enum settings: single-value, internally compared via === or == against
	 * an SMW_* integer constant. Shape: [ settingKey => [ stringName => SMW_* ] ].
	 *
	 * @var array<string, array<string, int>>
	 */
	private const ENUM_MAP = [
		'smwgShowFactbox' => [
			'hidden'   => SMW_FACTBOX_HIDDEN,
			'special'  => SMW_FACTBOX_SPECIAL,
			'nonempty' => SMW_FACTBOX_NONEMPTY,
			'shown'    => SMW_FACTBOX_SHOWN,
		],
		'smwgShowFactboxEdit' => [
			'hidden'   => SMW_FACTBOX_HIDDEN,
			'special'  => SMW_FACTBOX_SPECIAL,
			'nonempty' => SMW_FACTBOX_NONEMPTY,
			'shown'    => SMW_FACTBOX_SHOWN,
		],
		'smwgQEqualitySupport' => [
			'none' => SMW_EQ_NONE,
			'some' => SMW_EQ_SOME,
			'full' => SMW_EQ_FULL,
		],
		'smwgQConceptCaching' => [
			'none' => CONCEPT_CACHE_NONE,
			'hard' => CONCEPT_CACHE_HARD,
			'all'  => CONCEPT_CACHE_ALL,
		],
		'smwgSparqlRepositoryFeatures' => [
			'none'            => SMW_SPARQL_NONE,
			'connection-ping' => SMW_SPARQL_CONNECTION_PING,
		],
		'smwgResultFormatsFeatures' => [
			'none'            => SMW_RF_NONE,
			'template-outsep' => SMW_RF_TEMPLATE_OUTSEP,
		],
	];

	/**
	 * Default internal value applied when an unknown string is supplied to
	 * an enum setting (after a structured-log warning). Mirrors the
	 * documented extension.json default for the same key.
	 *
	 * @var array<string, int>
	 */
	private const ENUM_DEFAULT = [
		'smwgShowFactbox'              => SMW_FACTBOX_HIDDEN,
		'smwgShowFactboxEdit'          => SMW_FACTBOX_NONEMPTY,
		'smwgQEqualitySupport'         => SMW_EQ_SOME,
		'smwgQConceptCaching'          => CONCEPT_CACHE_HARD,
		'smwgSparqlRepositoryFeatures' => SMW_SPARQL_NONE,
		'smwgResultFormatsFeatures'    => SMW_RF_TEMPLATE_OUTSEP,
	];

	/**
	 * Flag settings: combined via bitwise OR, internally tested via & or
	 * {@see \SMW\Options::isFlagSet()}. Shape: [ settingKey => [ stringName => SMW_* ] ].
	 *
	 * @var array<string, array<string, int>>
	 */
	private const FLAG_MAP = [
		'smwgFactboxFeatures' => [
			'cache'              => SMW_FACTBOX_CACHE,
			'purge-refresh'      => SMW_FACTBOX_PURGE_REFRESH,
			'display-subobject'  => SMW_FACTBOX_DISPLAY_SUBOBJECT,
			'display-attachment' => SMW_FACTBOX_DISPLAY_ATTACHMENT,
		],
		'smwgQFeatures' => [
			'property'    => SMW_PROPERTY_QUERY,
			'category'    => SMW_CATEGORY_QUERY,
			'concept'     => SMW_CONCEPT_QUERY,
			'namespace'   => SMW_NAMESPACE_QUERY,
			'conjunction' => SMW_CONJUNCTION_QUERY,
			'disjunction' => SMW_DISJUNCTION_QUERY,
		],
		'smwgQConceptFeatures' => [
			'property'    => SMW_PROPERTY_QUERY,
			'category'    => SMW_CATEGORY_QUERY,
			'concept'     => SMW_CONCEPT_QUERY,
			'namespace'   => SMW_NAMESPACE_QUERY,
			'conjunction' => SMW_CONJUNCTION_QUERY,
			'disjunction' => SMW_DISJUNCTION_QUERY,
		],
		'smwgQSortFeatures' => [
			'sort'          => SMW_QSORT,
			'random'        => SMW_QSORT_RANDOM,
			'unconditional' => SMW_QSORT_UNCONDITIONAL,
		],
		'smwgSparqlQFeatures' => [
			'redirects'     => SMW_SPARQL_QF_REDI,
			'subproperties' => SMW_SPARQL_QF_SUBP,
			'subcategories' => SMW_SPARQL_QF_SUBC,
			'collation'     => SMW_SPARQL_QF_COLLATION,
			'no-case'       => SMW_SPARQL_QF_NOCASE,
		],
		'smwgCategoryFeatures' => [
			'redirect'  => SMW_CAT_REDIRECT,
			'instance'  => SMW_CAT_INSTANCE,
			'hierarchy' => SMW_CAT_HIERARCHY,
		],
		'smwgBrowseFeatures' => [
			'toolbox-link'  => SMW_BROWSE_TLINK,
			'show-inverse'  => SMW_BROWSE_SHOW_INVERSE,
			'show-incoming' => SMW_BROWSE_SHOW_INCOMING,
			'show-group'    => SMW_BROWSE_SHOW_GROUP,
			'show-sortkey'  => SMW_BROWSE_SHOW_SORTKEY,
			'use-api'       => SMW_BROWSE_USE_API,
		],
		'smwgAdminFeatures' => [
			'refresh'                      => SMW_ADM_REFRESH,
			'disposal'                     => SMW_ADM_DISPOSAL,
			'setup'                        => SMW_ADM_SETUP,
			'pstats'                       => SMW_ADM_PSTATS,
			'fullt'                        => SMW_ADM_FULLT,
			'maintenance-script-docs'      => SMW_ADM_MAINTENANCE_SCRIPT_DOCS,
			'show-overview'                => SMW_ADM_SHOW_OVERVIEW,
			'alert-last-optimization-run'  => SMW_ADM_ALERT_LAST_OPTIMIZATION_RUN,
		],
		'smwgParserFeatures' => [
			'strict'            => SMW_PARSER_STRICT,
			'unstrip'           => SMW_PARSER_UNSTRIP,
			'inline-errors'     => SMW_PARSER_INL_ERROR,
			'hidden-categories' => SMW_PARSER_HID_CATS,
			'links-in-values'   => SMW_PARSER_LINKS_IN_VALUES,
		],
		'smwgDVFeatures' => [
			'provider-redirect'      => SMW_DV_PROV_REDI,
			'monolingual-langcode'   => SMW_DV_MLTV_LCODE,
			'number-value-usespaces' => SMW_DV_NUMV_USPACE,
			'pattern-validation'     => SMW_DV_PVAP,
			'wpv-display-title'      => SMW_DV_WPV_DTITLE,
			'provider-display-title' => SMW_DV_PROV_DTITLE,
			'unique-constraint'      => SMW_DV_PVUC,
			'time-calendar-model'    => SMW_DV_TIMEV_CM,
			'preferred-label'        => SMW_DV_PPLB,
			'provider-link-hint'     => SMW_DV_PROV_LHNT,
			'wpv-pipetrick'          => SMW_DV_WPV_PIPETRICK,
		],
		'smwgFulltextSearchIndexableDataTypes' => [
			'blob'     => SMW_FT_BLOB,
			'uri'      => SMW_FT_URI,
			'wikipage' => SMW_FT_WIKIPAGE,
		],
		'smwgRemoteReqFeatures' => [
			'send-response' => SMW_REMOTE_REQ_SEND_RESPONSE,
			'show-note'     => SMW_REMOTE_REQ_SHOW_NOTE,
		],
		'smwgExperimentalFeatures' => [
			'queryresult-prefetch'   => SMW_QUERYRESULT_PREFETCH,
			'showparser-curtailment' => SMW_SHOWPARSER_USE_CURTAILMENT,
		],
		'smwgFieldTypeFeatures' => [
			'char-nocase' => SMW_FIELDT_CHAR_NOCASE,
			'char-long'   => SMW_FIELDT_CHAR_LONG,
		],
		'smwgQueryProfiler' => [
			'parameters' => SMW_QPRFL_PARAMS,
			'duration'   => SMW_QPRFL_DUR,
		],
	];

	/**
	 * Normalize a user-supplied config value for one of the registered keys.
	 * Keys not in any map are passed through unchanged.
	 *
	 * @since 7.0.0
	 */
	public static function normalize( string $key, mixed $value ): mixed {
		if ( isset( self::ENUM_MAP[$key] ) ) {
			return self::normalizeEnum( $key, $value );
		}
		if ( isset( self::FLAG_MAP[$key] ) ) {
			return self::normalizeFlags( $key, $value );
		}
		return $value;
	}

	/**
	 * Whether a deprecation notice has been emitted for the given setting
	 * key in the current request. Primarily for test introspection.
	 *
	 * @since 7.0.0
	 */
	public static function wasDeprecationEmitted( string $key ): bool {
		return isset( self::$deprecationEmitted[$key] );
	}

	/**
	 * Reverse-lookup: given the integer value of a legacy `SMW_*` /
	 * `CONCEPT_CACHE_*` constant for a registered enum or flag setting,
	 * return the equivalent kebab-string name. Used by test infrastructure
	 * that needs to upgrade legacy constant names (e.g. JSONScript test
	 * fixtures from external extensions) to the new form before they reach
	 * {@see Settings::set()}, avoiding the deprecation path entirely.
	 *
	 * @since 7.0.0
	 */
	public static function getStringFormForConstant( string $key, int $constantValue ): ?string {
		$map = self::ENUM_MAP[$key] ?? self::FLAG_MAP[$key] ?? null;
		if ( $map === null ) {
			return null;
		}
		$name = array_search( $constantValue, $map, true );
		return $name === false ? null : $name;
	}

	/**
	 * Reset the per-request deprecation suppression set. Intended for test
	 * isolation; production callers should never need this.
	 *
	 * @since 7.0.0
	 */
	public static function resetDeprecationState(): void {
		self::$deprecationEmitted = [];
	}

	private static function normalizeEnum( string $key, mixed $value ): mixed {
		$map = self::ENUM_MAP[$key];

		if ( is_string( $value ) ) {
			if ( array_key_exists( $value, $map ) ) {
				return $map[$value];
			}
			self::warnUnknown( $key, $value );
			return self::ENUM_DEFAULT[$key] ?? $value;
		}

		if ( in_array( $value, $map, true ) ) {
			self::emitDeprecation( $key );
			return $value;
		}

		return $value;
	}

	private static function normalizeFlags( string $key, mixed $value ): int|false {
		$map = self::FLAG_MAP[$key];

		if ( is_int( $value ) ) {
			// `$smwgFooFeatures = 0;` is the documented "no flags" form and the
			// value-equivalent of `[]` in the new form. It is not really a
			// legacy SMW_* bitmask, so don't bother the admin with a deprecation
			// notice for it. Any other integer is treated as legacy.
			if ( $value !== 0 ) {
				self::emitDeprecation( $key );
			}
			return $value;
		}

		if ( $value === false ) {
			// `smwgFieldTypeFeatures` distinguishes `false` (sentinel: feature
			// fully disabled, do not register the component) from `0` (no
			// flags set) at SetupFile.php's `!== false` check.
			// `smwgQueryProfiler` likewise distinguishes `false` (sentinel:
			// profiling skipped entirely) from `0` (profile created, no
			// extra detail fields) at AskParserFunction's `=== false`
			// short-circuit. Both sentinels are preserved so downstream
			// behaviour is unchanged.
			if ( $key === 'smwgFieldTypeFeatures' || $key === 'smwgQueryProfiler' ) {
				return false;
			}
			return 0;
		}

		if ( $value === true && $key === 'smwgQueryProfiler' ) {
			// `$smwgQueryProfiler = true;` was the historical "enabled, no
			// detail fields" form. The new canonical form is `[]`; both
			// yield `(int) $value & FLAG == 0` for every flag, so downstream
			// `isFlagSet` returns false in either case. Deprecate so admins
			// migrate before 8.0 drops `true` support along with the SMW_*
			// constants.
			self::emitDeprecation( $key );
			return 0;
		}

		if ( is_string( $value ) ) {
			$value = [ $value ];
		}

		if ( !is_array( $value ) ) {
			return 0;
		}

		$bitmask = 0;
		$sawLegacy = false;
		foreach ( $value as $element ) {
			if ( is_int( $element ) ) {
				$bitmask |= $element;
				$sawLegacy = true;
				continue;
			}
			if ( is_string( $element ) && array_key_exists( $element, $map ) ) {
				$bitmask |= $map[$element];
				continue;
			}
			if ( is_string( $element ) ) {
				self::warnUnknown( $key, $element );
			}
		}
		if ( $sawLegacy ) {
			self::emitDeprecation( $key );
		}
		return $bitmask;
	}

	private static function emitDeprecation( string $key ): void {
		if ( isset( self::$deprecationEmitted[$key] ) ) {
			return;
		}
		// Mark BEFORE the wfDeprecatedMsg call so wasDeprecationEmitted()
		// returns the right answer in unit tests where wfDeprecatedMsg is a
		// no-op (the function_exists guard below skips it).
		self::$deprecationEmitted[$key] = true;
		if ( function_exists( 'wfDeprecatedMsg' ) ) {
			wfDeprecatedMsg(
				"\${$key} using SMW_* integer constants is deprecated; switch to the string/array form documented in RELEASE-NOTES-7.0.0.md. The constants will be removed in SMW 8.0.",
				'7.0',
				'SemanticMediaWiki'
			);
		}
	}

	private static function warnUnknown( string $key, string $element ): void {
		if ( !class_exists( LoggerFactory::class ) ) {
			return;
		}
		LoggerFactory::getInstance( 'SMW' )->warning(
			"Unknown value '{element}' for \${key}; ignored. See RELEASE-NOTES-7.0.0.md for accepted values.",
			[ 'element' => $element, 'key' => $key ]
		);
	}
}
