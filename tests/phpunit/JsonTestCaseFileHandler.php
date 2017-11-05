<?php

namespace SMW\Tests;

use RuntimeException;
use SMW\Tests\Utils\File\JsonFileReader;
use SMW\Localizer;

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
	 * @since 2.4
	 *
	 * @param array $case
	 * @param string $identifier
	 *
	 * @return boolean
	 */
	public function requiredToSkipFor( array $case, $identifier ) {

		$skipOn = isset( $case['skip-on'] ) ? $case['skip-on'] : array();
		$identifier = strtolower( $identifier );

		$mwVersion = $GLOBALS['wgVersion'];

		foreach ( $skipOn as $id => $reason ) {

			if ( $identifier === $id ) {
				return true;
			}

			if ( strpos( $id, 'hhvm-' ) !== false && defined( 'HHVM_VERSION' ) ) {
				$this->reasonToSkip = "HHVM " . HHVM_VERSION . " version is not supported ({$reason})";
				return true;
			}

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

		$skipOn = isset( $meta['skip-on'] ) ? $meta['skip-on'] : array();

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
		$skipOn = isset( $meta['skip-on'] ) ? $meta['skip-on'] : array();

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
			return call_user_func_array( $callback, array( $settings[$key] ) );
		}

		// Needs special attention due to NS constant usage
		if ( $key === 'smwgNamespacesWithSemanticLinks' && isset( $settings[$key] ) ) {
			$smwgNamespacesWithSemanticLinks = array();

			foreach ( $settings[$key] as $ns => $value ) {
				$smwgNamespacesWithSemanticLinks[constant( $ns )] = (bool)$value;
			}

			return $smwgNamespacesWithSemanticLinks;
		}

		$constantFeaturesList = array(
			'smwgSparqlQFeatures',
			'smwgDVFeatures',
			'smwgFulltextSearchIndexableDataTypes',
			'smwgFieldTypeFeatures',
			'smwgQueryProfiler',
			'smwgParserFeatures',
			'smwgCategoryFeatures'
		);

		foreach ( $constantFeaturesList as $constantFeatures ) {
			if ( $key === $constantFeatures && isset( $settings[$key] ) ) {
				$features = '';

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
		if ( $key === 'smwgLinksInValues' && isset( $settings[$key] ) ) {
			return is_string( $settings[$key] ) ? constant( $settings[$key] ) : $settings[$key];
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
			$contents = array();
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
		return isset( $contents[$type] ) ? $contents[$type] : array();
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

	private function getFileContentsFor( $index ) {

		$contents = $this->fileReader->read();

		if ( isset( $contents[$index] ) ) {
			return $contents[$index];
		}

		throw new RuntimeException( "{$index} is unknown" );
	}

}
