<?php

namespace SMW\Tests\Utils\JSONScript;

use Exception;
use RuntimeException;
use SMW\Setup\LegacyConstantNormalizer;
use SMW\Tests\Utils\File\JsonFileReader;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 */
class JsonTestCaseFileHandler {

	/**
	 * @var string
	 */
	private $reasonToSkip = '';

	/**
	 * @since 2.2
	 */
	public function __construct( private readonly JsonFileReader $fileReader ) {
	}

	/**
	 * @since 2.2
	 *
	 * @return bool
	 */
	public function isIncomplete() {
		$meta = $this->getFileContentsFor( 'meta' );
		$isIncomplete = isset( $meta['is-incomplete'] ) ? (bool)$meta['is-incomplete'] : false;

		if ( $isIncomplete ) {
			$this->reasonToSkip = '"' . $this->getFileContentsFor( 'description' ) . '" has been marked as incomplete.';
		}

		return $isIncomplete;
	}

	/**
	 * @since 2.2
	 *
	 * @return bool
	 */
	public function getDebugMode() {
		$meta = $this->getFileContentsFor( 'meta' );

		return isset( $meta['debug'] ) ? (bool)$meta['debug'] : false;
	}

	/**
	 * @since 3.0
	 *
	 * @return bool
	 */
	public function hasAllRequirements( $dependencyDef = [] ) {
		$requires = $this->getContentsFor( 'requires' );

		if ( $requires === [] ) {
			return true;
		}

		foreach ( $requires as $key => $value ) {
			$res = false;

			if ( isset( $dependencyDef[$key] ) && is_callable( $dependencyDef[$key] ) ) {
				$res = call_user_func_array( $dependencyDef[$key], [ $value, &$this->reasonToSkip ] );
			}

			if ( $res === false ) {

				// Default msg!
				if ( $this->reasonToSkip === '' ) {
					$this->reasonToSkip = "$key requirements were not met!";
				}

				return false;
			}
		}

		return true;
	}

	/**
	 * @since 2.4
	 *
	 * @param array $case
	 * @param string $identifier
	 *
	 * @return bool
	 */
	public function requiredToSkipFor( array $case, $identifier ) {
		$skipOn = isset( $case['skip-on'] ) ? $case['skip-on'] : [];
		$identifier = strtolower( $identifier );

		if ( is_bool( $skipOn ) ) {
			return $skipOn;
		}

		if ( !is_array( $skipOn ) ) {
			throw new RuntimeException( "skip-on should be an array or boolean value" );
		}

		// Transform for convenience `skip-except` meaning skip all
		// except for ...
		if ( isset( $case['skip-except'] ) ) {
			foreach ( $case['skip-except'] as $id => $value ) {
				$skipOn[$id] = [ "not", $value ];
			}
		}

		$version = MW_VERSION;

		foreach ( $skipOn as $id => $value ) {

			$versionToSkip = '';
			$compare = '=';
			$noop = '';

			if ( is_array( $value ) ) {
				$versionToSkip = (string)$value[0];
				$reason = $value[1];
			} else {
				$reason = $value;
			}

			// Support for { "skip-on": { "foo": [ "not", "Exclude all except foo ..." ] }
			if ( $versionToSkip === 'not' && $identifier === $id ) {
				continue;
			} elseif ( $versionToSkip === 'not' && $identifier !== $id ) {
				return true;
			}

			// Support for { "skip-on": { "postgres": [ "<9.2", "Reason..." }
			if ( $identifier === 'postgres' && $identifier === $id && $versionToSkip !== '' ) {
				$version = SMW_PHPUNIT_DB_VERSION;
			// Support for { "skip-on": { "virtuoso": "Virtuoso 6.1 ..." }
			} elseif ( $identifier === $id ) {
				return true;
			}

			// Support for { "skip-on": { "smw->2.5.x": "Reason is ..." }
			// or { "skip-on": { "mw->1.30.x": "Reason is ..." }
			if ( strpos( $id, 'mw-' ) !== false ) {
				[ $noop, $versionToSkip ] = explode( "mw-", $id, 2 );
			}

			// Support for { "skip-on": { "mediawiki": [ ">1.29.x", "Reason is ..." ] }
			if ( strpos( $id, 'smw' ) !== false ) {
				$version = SMW_VERSION;
			} elseif ( strpos( $id, 'mediawiki' ) !== false || strpos( $id, 'mw' ) !== false ) {
				$version = MW_VERSION;
			} elseif ( strpos( $id, 'php' ) !== false ) {
				$version = defined( 'PHP_VERSION' ) ? PHP_VERSION : 0;
			}

			if ( $versionToSkip !== '' && ( $versionToSkip[0] === '<' || $versionToSkip[0] === '>' ) ) {
				$compare = $versionToSkip[0];
				$versionToSkip = substr( $versionToSkip, 1 );
			}

			if ( strpos( $versionToSkip, '.x' ) ) {
				$versionToSkip = str_replace( '.x', '.9999', $versionToSkip );
				$compare = $compare === '=' ? '<' : $compare;
			}

			if ( strpos( $versionToSkip, '<' ) ) {
				$versionToSkip = str_replace( '<', '', $versionToSkip );
				$compare = '<';
			}

			// Skip any version as in { "skip-on": { "mediawiki": [ "*", "Reason is ..." ] }
			if ( $versionToSkip === '*' ) {
				return true;
			}

			if ( version_compare( $version, $versionToSkip, $compare ) ) {
				$this->reasonToSkip = "Version $version is not supported ({$reason})";
				return true;
			}
		}

		return false;
	}

	/**
	 * @since 2.2
	 *
	 * @return bool
	 */
	public function requiredToSkipForConnector( $connectorId ) {
		$connectorId = strtolower( $connectorId );
		$meta = $this->getFileContentsFor( 'meta' );

		$skipOn = isset( $meta['skip-on'] ) ? $meta['skip-on'] : [];

		if ( array_key_exists( $connectorId, $skipOn ) ) {
			$this->reasonToSkip = $skipOn[$connectorId];
		}

		return $this->reasonToSkip !== '';
	}

	/**
	 * @since 2.2
	 *
	 * @return bool
	 */
	public function requiredToSkipForJsonVersion( $version ) {
		$meta = $this->getFileContentsFor( 'meta' );

		if ( version_compare( $version, $meta['version'], 'ne' ) ) {
			$this->reasonToSkip = 'The test case file version `' . $meta['version'] . "` is not supported due to `{$version}` being required as version.";
		}

		return $this->reasonToSkip !== '';
	}

	/**
	 * @since 2.2
	 *
	 * @return bool
	 */
	public function requiredToSkipOnSiteLanguage( $siteLanguage ) {
		$meta = $this->getFileContentsFor( 'meta' );
		$skipOn = isset( $meta['skip-on'] ) ? $meta['skip-on'] : [];

		foreach ( $skipOn as $id => $reason ) {

			if ( $id !== 'sitelanguage' ) {
				continue;
			}

			if ( $reason[0] === $siteLanguage ) {
				$this->reasonToSkip = $reason[1];
				break;
			}
		}

		return $this->reasonToSkip !== '';
	}

	/**
	 * @since 2.2
	 *
	 * @return bool
	 */
	public function requiredToSkipForMwVersion( $mwVersion ) {
		$meta = $this->getFileContentsFor( 'meta' );
		$skipOn = isset( $meta['skip-on'] ) ? $meta['skip-on'] : [];

		foreach ( $skipOn as $id => $reason ) {
			if ( strpos( $id, 'mediawiki' ) === false ) {
				continue;
			}

			$versionToSkip = $skipOn['mediawiki'][0];
			$compare = '=';

			if ( strpos( $versionToSkip, '=' ) ) {
				$list = explode( "=", $versionToSkip );
				$compare = $list[0] . '=';
				if ( strpos( $versionToSkip, '.x' ) ) {
					$versionToSkip = str_replace( '.x', '.9999', $list[1] );
				}
			}

			if ( !strpos( $versionToSkip, '=' ) ) {
				$compare = $versionToSkip[0];
				$list = explode( $compare, $versionToSkip );
				if ( strpos( $versionToSkip, '.x' ) ) {
					$versionToSkip = str_replace( '.x', '.9999', $list[1] );
				}
			}

			if ( version_compare( $mwVersion, $versionToSkip, $compare ) ) {
				$messageToShow = is_array( $reason ) && isset( $reason[1] ) ? $reason[1] : 'Test skipped!';
				$this->reasonToSkip = "MediaWiki " . $mwVersion . " version is not supported ({$messageToShow})";
				break;
			}
		}

		return $this->reasonToSkip !== '';
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getReasonForSkip() {
		return $this->reasonToSkip;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key
	 *
	 * @return booleam
	 */
	public function hasSetting( $key ) {
		$settings = $this->getFileContentsFor( 'settings' );

		return isset( $settings[$key] );
	}

	/**
	 * @since 2.2
	 *
	 * @return array|int|string|bool
	 * @throws RuntimeException
	 */
	public function getSettingsFor( $key, $callback = null ) {
		$settings = $this->getFileContentsFor( 'settings' );

		if ( isset( $settings[$key] ) && is_callable( $callback ) ) {
			return call_user_func_array( $callback, [ $settings[$key] ] );
		}

		// Needs special attention due to NS constant usage
		if ( $key === 'smwgNamespacesWithSemanticLinks' && isset( $settings[$key] ) ) {
			$smwgNamespacesWithSemanticLinks = [];

			foreach ( $settings[$key] as $ns => $value ) {
				$smwgNamespacesWithSemanticLinks[constant( $ns )] = (bool)$value;
			}

			return $smwgNamespacesWithSemanticLinks;
		}

		// Flag-bitmask settings. Test cases write values in the new
		// kebab-string array form (e.g. `[ "strict", "inline-errors" ]`);
		// older fixtures from external extensions may still use the
		// legacy `SMW_*` constant names, which we transparently upgrade
		// so the value reaches Settings::set() in the new form and never
		// trips LegacyConstantNormalizer's deprecation path.
		$flagSettings = [
			'smwgSparqlQFeatures',
			'smwgDVFeatures',
			'smwgFulltextSearchIndexableDataTypes',
			'smwgFieldTypeFeatures',
			'smwgQueryProfiler',
			'smwgParserFeatures',
			'smwgCategoryFeatures',
			'smwgQSortFeatures',
		];

		if ( in_array( $key, $flagSettings, true ) && isset( $settings[$key] ) ) {
			return $this->normalizeFlagSettingValue( $key, $settings[$key] );
		}

		if ( $key === 'smwgQEqualitySupport' && isset( $settings[$key] ) ) {
			return $this->normalizeEnumSettingValue( $key, $settings[$key] );
		}

		if ( $key === 'wgDefaultUserOptions' && isset( $settings[$key] ) ) {
			return array_merge( $GLOBALS['wgDefaultUserOptions'], $settings[$key] );
		}

		if ( $key === 'smwgQConceptCaching' && isset( $settings[$key] ) ) {
			return $this->normalizeEnumSettingValue( $key, $settings[$key] );
		}

		// Needs special attention due to constant usage
		if ( strpos( $key, 'CacheType' ) !== false && isset( $settings[$key] ) ) {
			return $settings[$key] === false ? CACHE_NONE : ( defined( $settings[$key] ) ? constant( $settings[$key] ) : $settings[$key] );
		}

		if ( isset( $settings[$key] ) ) {
			return $settings[$key];
		}

		// Return values from the global settings as default
		if ( isset( $GLOBALS[$key] ) || array_key_exists( $key, $GLOBALS ) ) {
			return $GLOBALS[$key];
		}

		// Key is unknown, TestConfig will remove any remains during tearDown
		return null;
	}

	/**
	 * Coerce a flag-bitmask setting value into the new kebab-string array
	 * form. Pass-through for arrays of kebab strings; for arrays containing
	 * legacy `SMW_*` constant names (still used by some external-extension
	 * JSONScript fixtures), reverse-translate each defined constant via
	 * {@see LegacyConstantNormalizer::getStringFormForConstant()} so the
	 * resulting value never reaches the deprecation path in Settings::set().
	 */
	private function normalizeFlagSettingValue( string $key, mixed $value ) {
		if ( !is_array( $value ) ) {
			return $value;
		}
		$out = [];
		foreach ( $value as $element ) {
			$out[] = $this->resolveLegacyConstantName( $key, $element );
		}
		return $out;
	}

	/**
	 * Coerce an enum setting value into the new string form. Pass-through
	 * for already-kebab strings; reverse-translate legacy `SMW_*` /
	 * `CONCEPT_CACHE_*` constant names via
	 * {@see LegacyConstantNormalizer::getStringFormForConstant()}.
	 */
	private function normalizeEnumSettingValue( string $key, mixed $value ) {
		return $this->resolveLegacyConstantName( $key, $value );
	}

	private function resolveLegacyConstantName( string $key, mixed $value ) {
		if ( !is_string( $value ) || !defined( $value ) ) {
			return $value;
		}
		$resolved = constant( $value );
		if ( !is_int( $resolved ) ) {
			return $value;
		}
		return LegacyConstantNormalizer::getStringFormForConstant( $key, $resolved ) ?? $value;
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getListOfProperties() {
		return $this->getFileContentsFor( 'properties' );
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getListOfSubjects() {
		return $this->getFileContentsFor( 'subjects' );
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getPageCreationSetupList() {
		return $this->getContentsFor( 'setup' );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $key
	 *
	 * @return array
	 */
	public function getContentsFor( $key ) {
		try {
			$contents = $this->getFileContentsFor( $key );
		} catch ( Exception $e ) {
			$contents = [];
		}

		return $contents;
	}

	/**
	 * @since 2.2
	 *
	 * @param string $key
	 *
	 * @return array
	 */
	public function findTestCasesFor( $key ) {
		return $this->getContentsFor( $key );
	}

	/**
	 * @since 2.5
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	public function findTasksBeforeTestExecutionByType( $type ) {
		$contents = $this->getContentsFor( 'beforeTest' );
		return isset( $contents[$type] ) ? $contents[$type] : [];
	}

	/**
	 * @since 2.5
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	public function findTestCasesByType( $type ) {
		return array_filter( $this->getContentsFor( 'tests' ), static function ( $contents ) use( $type ) {
			return isset( $contents['type'] ) && $contents['type'] === $type;
		} );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @return int
	 */
	public function countTestCasesByType( $type ) {
		return count( $this->findTestCasesByType( $type ) );
	}

	private function getFileContentsFor( $index ) {
		$contents = $this->fileReader->read();

		if ( isset( $contents[$index] ) ) {
			return $contents[$index];
		}

		throw new RuntimeException( "{$index} is unknown" );
	}

}
