<?php

namespace SMW;

use Onoi\MessageReporter\MessageReporterAwareTrait;
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

	use MessageReporterAwareTrait;

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
	public static function loadSchema( &$vars ) {

		// @see #3506
		$file = File::dir( $vars['smwgConfigFileDir'] . '/.smw.json' );

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
			isset( $GLOBALS['smw.json'][$id]['in.maintenance_mode'] ) &&
			$GLOBALS['smw.json'][$id]['in.maintenance_mode'] === true ) {
			$isGoodSchema = false;
		}

		return $isGoodSchema;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $vars
	 *
	 * @return boolean
	 */
	public static function isMaintenanceMode( $vars ) {

		if ( !defined( 'MW_PHPUNIT_TEST' ) && ( PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg' ) ) {
			return false;
		}

		$id = Site::id();

		if ( !isset( $vars['smw.json'][$id]['in.maintenance_mode'] ) ) {
			return false;
		}

		return $vars['smw.json'][$id]['in.maintenance_mode'] === true;
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
			Installer::POPULATE_HASH_FIELD_COMPLETE => [ false, 'smw-install-incomplete-populate-hash-field' ]
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
	 * @param boolean $isCli
	 *
	 * @return boolean
	 */
	public function setMaintenanceMode( $vars ) {

		// #3563, Use the specific wiki-id as identifier for the instance in use
		$key = self::makeUpgradeKey( $vars );
		$id = Site::id();

		if ( $this->messageReporter !== null ) {
			$this->messageReporter->reportMessage( "Switching into the maintenance mode for $id ..." );
		}

		$this->write( $vars, [ 'upgrade_key' => $key, 'in.maintenance_mode' => true ] );

		if ( $this->messageReporter !== null ) {
			$this->messageReporter->reportMessage( "\n   ... done.\n\n" );
		}
	}

	/**
	 * @since 3.1
	 *
	 * @param array $vars
	 */
	public function setUpgradeKey( $vars ) {

		// #3563, Use the specific wiki-id as identifier for the instance in use
		$key = self::makeUpgradeKey( $vars );
		$id = Site::id();

		if (
			isset( $vars['smw.json'][$id]['upgrade_key'] ) &&
			$key === $vars['smw.json'][$id]['upgrade_key'] &&
			$vars['smw.json'][$id]['in.maintenance_mode'] === false ) {
			return false;
		}

		if ( $this->messageReporter !== null ) {
			$this->messageReporter->reportMessage( "\nReleasing the maintenance mode for $id ..." );
		}

		$this->write( $vars, [ 'upgrade_key' => $key, 'in.maintenance_mode' => false ] );

		if ( $this->messageReporter !== null ) {
			$this->messageReporter->reportMessage( "\n   ... done.\n" );
		}
	}

	/**
	 * @since 3.1
	 *
	 * @param array $vars
	 * @param array $args
	 */
	public function write( $vars, $args = [] ) {

		$configFile = File::dir( $vars['smwgConfigFileDir'] . '/.smw.json' );
		$id = Site::id();

		if ( !isset( $vars['smw.json'] ) ) {
			$vars['smw.json'] = [];
		}

		foreach ( $args as $key => $value ) {
			$vars['smw.json'][$id][$key] = $value;
		}

		// Log the base elements used for computing the key
		// $vars['smw.json'][$id]['upgrade_key_base'] = self::makeKey(
		//	$vars
		// );

		// Remove legacy
		if ( isset( $vars['smw.json']['upgradeKey'] ) ) {
			unset( $vars['smw.json']['upgradeKey'] );
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
			$vars['smwgFixedProperties'],
			$vars['smwgEnabledFulltextSearch'],
			$pageSpecialProperties
		];

		return json_encode( $components );
	}

}
