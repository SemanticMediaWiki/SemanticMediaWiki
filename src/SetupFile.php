<?php

namespace SMW;

use SMW\Exception\FileNotWritableException;
use SMW\Utils\File;
use SMW\SQLStore\Installer;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SetupFile {

	/**
	 * Describes the maintenance mode
	 */
	const MAINTENANCE_MODE = 'maintenance_mode';

	/**
	 * Describes the upgrade key
	 */
	const UPGRADE_KEY = 'upgrade_key';

	/**
	 * Describes the file name
	 */
	const FILE_NAME = '.smw.json';

	/**
	 * @var File
	 */
	private $file;

	/**
	 * @since 3.1
	 *
	 * @param File|null $file
	 */
	public function __construct( File $file = null ) {
		$this->file = $file;

		if ( $this->file === null ) {
			$this->file = new File();
		}
	}

	/**
	 * @since 3.1
	 *
	 * @param array $vars
	 */
	public function loadSchema( &$vars = [] ) {

		if ( $vars === [] ) {
			$vars = $GLOBALS;
		}

		if ( isset( $vars['smw.json'] ) ) {
			return;
		}

		// @see #3506
		$file = File::dir( $vars['smwgConfigFileDir'] . '/' . self::FILE_NAME );

		// Doesn't exist? The `Setup::init` will take care of it by trying to create
		// a new file and if it fails or unable to do so wail raise an exception
		// as we expect to have access to it.
		if ( is_readable( $file ) ) {
			$vars['smw.json'] = json_decode( file_get_contents( $file ), true );
		}
	}

	/**
	 * @since 3.1
	 *
	 * @param boolean $isCli
	 *
	 * @return boolean
	 */
	public static function isGoodSchema( $isCli = false ) {

		if ( $isCli && defined( 'MW_PHPUNIT_TEST' ) ) {
			return true;
		}

		if ( $isCli === false && ( PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg' ) ) {
			return true;
		}

		// #3563, Use the specific wiki-id as identifier for the instance in use
		$id = Site::id();

		if ( !isset( $GLOBALS['smw.json'][$id]['upgrade_key'] ) ) {
			return false;
		}

		$isGoodSchema = self::makeUpgradeKey( $GLOBALS ) === $GLOBALS['smw.json'][$id]['upgrade_key'];

		if (
			isset( $GLOBALS['smw.json'][$id][self::MAINTENANCE_MODE] ) &&
			$GLOBALS['smw.json'][$id][self::MAINTENANCE_MODE] !== false ) {
			$isGoodSchema = false;
		}

		return $isGoodSchema;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $vars
	 *
	 * @return string
	 */
	public static function makeUpgradeKey( $vars ) {
		return sha1( self::makeKey( $vars ) );
	}

	/**
	 * @since 3.1
	 *
	 * @param array $vars
	 *
	 * @return boolean
	 */
	public function inMaintenanceMode( $vars = [] ) {

		if ( !defined( 'MW_PHPUNIT_TEST' ) && ( PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg' ) ) {
			return false;
		}

		if ( $vars === [] ) {
			$vars = $GLOBALS;
		}

		$id = Site::id();

		if ( !isset( $vars['smw.json'][$id][self::MAINTENANCE_MODE] ) ) {
			return false;
		}

		return $vars['smw.json'][$id][self::MAINTENANCE_MODE] !== false;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $vars
	 *
	 * @return []
	 */
	public function getMaintenanceMode( $vars = [] ) {

		if ( $vars === [] ) {
			$vars = $GLOBALS;
		}

		$id = Site::id();

		if ( !isset( $vars['smw.json'][$id][self::MAINTENANCE_MODE] ) ) {
			return [];
		}

		return $vars['smw.json'][$id][self::MAINTENANCE_MODE];
	}

	/**
	 * @since 3.1
	 *
	 * @param array $vars
	 *
	 * @return []
	 */
	public static function findIncompleteTasks( $vars ) {

		$id = Site::id();
		$tasks = [];

		// Key field => [ value that constitutes the `INCOMPLETE` state, error msg ]
		$checks = [
			\SMW\SQLStore\Installer::POPULATE_HASH_FIELD_COMPLETE => [ false, 'smw-install-incomplete-populate-hash-field' ],
			\SMW\Elastic\ElasticStore::REBUILD_INDEX_RUN_COMPLETE => [ false, 'smw-install-incomplete-elasticstore-indexrebuild' ]
		];

		foreach ( $checks as $key => $value ) {

			if ( !isset( $vars['smw.json'][$id][$key] ) ) {
				continue;
			}

			if ( $vars['smw.json'][$id][$key] === $value[0] ) {
				$tasks[] = $value[1];
			}
		}

		return $tasks;
	}

	/**
	 * @since 3.1
	 *
	 * @param mixed $maintenanceMode
	 */
	public function setMaintenanceMode( $maintenanceMode, $vars = [] ) {

		if ( $vars === [] ) {
			$vars = $GLOBALS;
		}

		$this->write(
			[
				self::UPGRADE_KEY => self::makeUpgradeKey( $vars ),
				self::MAINTENANCE_MODE => $maintenanceMode
			],
			$vars
		);
	}

	/**
	 * @since 3.1
	 *
	 * @param array $vars
	 */
	public function finalize( $vars = [] ) {

		if ( $vars === [] ) {
			$vars = $GLOBALS;
		}

		// #3563, Use the specific wiki-id as identifier for the instance in use
		$key = self::makeUpgradeKey( $vars );
		$id = Site::id();

		if (
			isset( $vars['smw.json'][$id][self::UPGRADE_KEY] ) &&
			$key === $vars['smw.json'][$id][self::UPGRADE_KEY] &&
			$vars['smw.json'][$id][self::MAINTENANCE_MODE] === false ) {
			return false;
		}

		$this->write(
			[
				self::UPGRADE_KEY => $key,
				self::MAINTENANCE_MODE => false
			],
			$vars
		);
	}

	/**
	 * @since 3.1
	 *
	 * @param array $vars
	 */
	public function reset( $vars = [] ) {

		if ( $vars === [] ) {
			$vars = $GLOBALS;
		}

		$id = Site::id();

		if ( !isset( $vars['smw.json'][$id] ) ) {
			return;
		}

		foreach ( $vars['smw.json'][$id] as $k => $v ) {
			$args[$k] = null;
		}

		$this->write( $args, $vars );
	}

	/**
	 * @since 3.1
	 *
	 * @param array $args
	 */
	public function set( array $args, $vars = [] ) {

		if ( $vars === [] ) {
			$vars = $GLOBALS;
		}

		$this->write( $args, $vars );
	}

	/**
	 * @since 3.1
	 *
	 * @param array $args
	 */
	public function get( $key, $vars = [] ) {

		if ( $vars === [] ) {
			$vars = $GLOBALS;
		}

		$id = Site::id();

		if ( isset( $vars['smw.json'][$id][$key] ) ) {
			return $vars['smw.json'][$id][$key];
		}

		return null;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $key
	 */
	public function remove( $key, $vars = [] ) {

		if ( $vars === [] ) {
			$vars = $GLOBALS;
		}

		$this->write( [ $key => null ], $vars );
	}

	/**
	 * @since 3.1
	 *
	 * @param array $vars
	 * @param array $args
	 */
	public function write( $args = [], array $vars ) {

		$configFile = File::dir( $vars['smwgConfigFileDir'] . '/' . self::FILE_NAME );
		$id = Site::id();

		if ( !isset( $vars['smw.json'] ) ) {
			$vars['smw.json'] = [];
		}

		foreach ( $args as $key => $value ) {
			// NULL means that the key key is removed
			if ( $value === null ) {
				unset( $vars['smw.json'][$id][$key] );
			} else {
				$vars['smw.json'][$id][$key] = $value;
			}
		}

		// Log the base elements used for computing the key
		// $vars['smw.json'][$id]['upgrade_key_base'] = self::makeKey(
		//	$vars
		// );

		// Remove legacy
		if ( isset( $vars['smw.json']['upgradeKey'] ) ) {
			unset( $vars['smw.json']['upgradeKey'] );
		}
		if ( isset( $vars['smw.json'][$id]['in.maintenance_mode'] ) ) {
			unset( $vars['smw.json'][$id]['in.maintenance_mode'] );
		}

		try {
			$this->file->write(
				$configFile,
				json_encode( $vars['smw.json'], JSON_PRETTY_PRINT )
			);
		} catch( FileNotWritableException $e ) {
			// Users may not have `wgShowExceptionDetails` enabled and would
			// therefore not see the exception error message hence we fail hard
			// and die
			die(
				"\n\nERROR: " . $e->getMessage() . "\n" .
				"\n       The \"smwgConfigFileDir\" setting should point to a" .
				"\n       directory that is persistent and writable!\n"
			);
		}
	}

	private static function makeKey( $vars ) {

		// Only recognize those properties that require a fixed table
		$pageSpecialProperties = array_intersect(
			// Special properties enabled?
			$vars['smwgPageSpecialProperties'],

			// Any custom fixed properties require their own table?
			TypesRegistry::getFixedProperties( 'custom_fixed' )
		);

		// Sort to ensure the key contains the same order
		sort( $vars['smwgFixedProperties'] );
		sort( $pageSpecialProperties );

		// The following settings influence the "shape" of the tables required
		// therefore use the content to compute a key that reflects any
		// changes to them
		$components = [
			$vars['smwgUpgradeKey'],
			$vars['smwgDefaultStore'],
			$vars['smwgFixedProperties'],
			$vars['smwgEnabledFulltextSearch'],
			$pageSpecialProperties
		];

		if ( $vars['smwgFieldTypeFeatures'] !== false ) {
			$components += [ 'smwgFieldTypeFeatures' => $vars['smwgFieldTypeFeatures'] ];
		}

		return json_encode( $components );
	}

}
