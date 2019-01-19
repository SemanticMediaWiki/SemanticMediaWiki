<?php

namespace SMW\Tests;

use RuntimeException;
use SMW\Tests\Utils\File\JsonFileReader;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class JsonTestCaseFileHandler {

	/**
	 * @var JsonFileReader
	 */
	private $fileReader;

	/**
	 * @var string
	 */
	private $reasonToSkip = '';

	/**
	 * @since 2.2
	 *
	 * @param JsonFileReader $fileReader
	 */
	public function __construct( JsonFileReader $fileReader ) {
		$this->fileReader = $fileReader;
	}

	/**
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function isIncomplete() {

		$meta = $this->getFileContentsFor( 'meta' );
		$isIncomplete = isset( $meta['is-incomplete'] ) ? (bool)$meta['is-incomplete'] : false;

		if ( $isIncomplete ) {
			$this->reasonToSkip = '"'. $this->getFileContentsFor( 'description' ) . '" has been marked as incomplete.';
		}

		return $isIncomplete;
	}

	/**
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function getDebugMode() {

		$meta = $this->getFileContentsFor( 'meta' );

		return isset( $meta['debug'] ) ? (bool)$meta['debug'] : false;
	}

	/**
	 * @since 3.0
	 *
	 * @return boolean
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
	 * @return boolean
	 */
	public function requiredToSkipFor( array $case, $identifier ) {

		$skipOn = isset( $case['skip-on'] ) ? $case['skip-on'] : [];
		$identifier = strtolower( $identifier );

		$version = MW_VERSION;

		foreach ( $skipOn as $id => $value ) {

			$versionToSkip = '';
			$compare = '=';
			$noop = '';

			if ( is_array( $value ) ) {
				$versionToSkip = $value[0];
				$reason = $value[1];
			} else {
				$reason = $value;
			}

			// Suppor for { "skip-on": { "foo": [ "not", "Exclude all except foo ..." ] }
			if ( $versionToSkip === 'not' && $identifier === $id ) {
				continue;
			} elseif ( $versionToSkip === 'not' && $identifier !== $id ) {
				return true;
			}

			// Suppor for { "skip-on": { "virtuoso": "Virtuoso 6.1 ..." }
			if ( $identifier === $id ) {
				return true;
			}

			// Suppor for { "skip-on": { "smw->2.5.x": "Reason is ..." }
			// or { "skip-on": { "mw->1.30.x": "Reason is ..." }
			if ( strpos( $id, 'mw-' ) !== false ) {
				list( $noop, $versionToSkip ) = explode( "mw-", $id, 2 );
			}

			if ( strpos( $id, 'hhvm-' ) !== false ) {
				list( $noop, $versionToSkip ) = explode( "hhvm-", $id, 2 );
			}

			// Suppor for { "skip-on": { "mediawiki": [ ">1.29.x", "Reason is ..." ] }
			if ( strpos( $id, 'smw' ) !== false ) {
				$version = SMW_VERSION;
			} elseif ( strpos( $id, 'mediawiki' ) !== false || strpos( $id, 'mw' ) !== false ) {
				$version = MW_VERSION;
			} elseif ( strpos( $id, 'hhvm' ) !== false ) {
				$version = defined( 'HHVM_VERSION' ) ? HHVM_VERSION : 0;
			} elseif ( strpos( $id, 'php' ) !== false ) {
				$version = defined( 'PHP_VERSION' ) ? PHP_VERSION : 0;
 			}

			if ( $versionToSkip !== '' && ( $versionToSkip{0} === '<' || $versionToSkip{0} === '>' ) ) {
				$compare = $versionToSkip{0};
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
				$this->reasonToSkip = "$version version is not supported ({$reason})";
				return true;
			}
		}

		return false;
	}

	/**
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function requiredToSkipForConnector( $connectorId ) {

		$connectorId = strtolower( $connectorId );
		$meta = $this->getFileContentsFor( 'meta' );

		$skipOn = isset( $meta['skip-on'] ) ? $meta['skip-on'] : [];

		if ( in_array( $connectorId, array_keys( $skipOn ) ) ) {
			$this->reasonToSkip = $skipOn[$connectorId];
		}

		return $this->reasonToSkip !== '';
	}

	/**
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function requiredToSkipForJsonVersion( $version ) {

		$meta = $this->getFileContentsFor( 'meta' );

		if ( version_compare( $version, $meta['version'], 'ne' ) ) {
			$this->reasonToSkip = $meta['version'] . " is not supported due to a required {$version} test case version.";
		}

		return $this->reasonToSkip !== '';
	}

	/**
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function requiredToSkipForMwVersion( $mwVersion ) {

		$meta = $this->getFileContentsFor( 'meta' );
		$skipOn = isset( $meta['skip-on'] ) ? $meta['skip-on'] : [];

		foreach ( $skipOn as $id => $reason ) {

			if ( strpos( $id, 'mw-' ) === false ) {
				continue;
			}

			list( $mw, $versionToSkip ) = explode( "mw-", $id, 2 );
			$compare = '=';

			if ( strpos( $versionToSkip, '.x' ) ) {
				$versionToSkip = str_replace( '.x', '.9999', $versionToSkip );
				$compare = '<';
			}

			if ( strpos( $versionToSkip, '<' ) ) {
				$versionToSkip = str_replace( '<', '', $versionToSkip );
				$compare = '<';
			}

			if ( version_compare( $mwVersion, $versionToSkip, $compare ) ) {
				$this->reasonToSkip = "MediaWiki " . $mwVersion . " version is not supported ({$reason})";
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
	 * @return array|integer|string|boolean
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

		$constantFeaturesList = [
			'smwgSparqlQFeatures',
			'smwgDVFeatures',
			'smwgFulltextSearchIndexableDataTypes',
			'smwgFieldTypeFeatures',
			'smwgQueryProfiler',
			'smwgParserFeatures',
			'smwgCategoryFeatures',
			'smwgQSortFeatures'
		];

		foreach ( $constantFeaturesList as $constantFeatures ) {
			if ( $key === $constantFeatures && isset( $settings[$key] ) ) {
				$features = '';

				if ( !is_array( $settings[$key] ) ) {
					return $settings[$key];
				}

				foreach ( $settings[$key] as $value ) {
					$features = constant( $value ) | (int)$features;
				}

				return $features;
			}
		}

		if ( $key === 'wgDefaultUserOptions' && isset( $settings[$key] ) ) {
			return array_merge( $GLOBALS['wgDefaultUserOptions'], $settings[$key] );
		}

		// Needs special attention due to constant usage
		if ( $key === 'smwgQConceptCaching' && isset( $settings[$key] ) ) {
			return constant( $settings[$key] );
		}

		// Needs special attention due to constant usage
		if ( strpos( $key, 'CacheType' ) !== false && isset( $settings[$key] ) ) {
			return $settings[$key] === false ? CACHE_NONE : defined( $settings[$key] ) ? constant( $settings[$key] ) : $settings[$key];
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

		try{
			$contents = $this->getFileContentsFor( $key );
		} catch( \Exception $e ) {
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
		return array_filter( $this->getContentsFor( 'tests' ), function( $contents ) use( $type ) {
			return isset( $contents['type'] ) && $contents['type'] === $type;
		} );
	}

	/**
	 * @since 3.0
	 *
	 * @param string $type
	 *
	 * @return integer
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
