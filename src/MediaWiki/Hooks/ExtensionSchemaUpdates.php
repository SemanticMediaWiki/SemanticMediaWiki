<?php

namespace SMW\MediaWiki\Hooks;

use DatabaseUpdater;
use Maintenance;
use ReflectionProperty;
use SMW\Options;
use SMW\SQLStore\Installer;

/**
 * Schema update to set up the needed database tables
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LoadExtensionSchemaUpdates
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ExtensionSchemaUpdates {

	/**
	 * @var DatabaseUpdater
	 */
	protected $updater = null;

	/**
	 * @since  2.0
	 *
	 * @param DatabaseUpdater $updater = null
	 */
	public function __construct( DatabaseUpdater $updater = null ) {
		$this->updater = $updater;
	}

	/**
	 * @since 2.0
	 *
	 * @return true
	 */
	public function process() {

		$verbose = true;

		$options = new Options(
			[
				Installer::OPT_SCHEMA_UPDATE => true,
				Installer::OPT_TABLE_OPTIMIZE => true,
				Installer::OPT_IMPORT => true,
				Installer::OPT_SUPPLEMENT_JOBS => true
			]
		);

		if ( $this->hasMaintenanceArg( 'skip-optimize' ) ) {
			$options->set( Installer::OPT_TABLE_OPTIMIZE, false );
		}

		// Needs a static caller otherwise the DatabaseUpdater returns with:
		// "Warning: call_user_func_array() expects parameter 1 to be a
		// valid callback ..."
		//
		// DatabaseUpdater notes "... $callback is the method to call; either a
		// DatabaseUpdater method name or a callable. Must be serializable (ie.
		// no anonymous functions allowed). The rest of the parameters (if any)
		// will be passed to the callback. ..."
		$this->updater->addExtensionUpdate(
			[
				'SMWStore::setupStore',
				[
					'verbose' => $verbose,
					'options' => $options
				]
			]
		);

		return true;
	}

	private function hasMaintenanceArg( $key ) {

		$maintenance = null;

		// We don't have access to the `update.php` internals due to lack
		// of public methods ... it is far from a clean approach but the only
		// way to fetch arguments invoked during the execution of `update.php`
		// Check required due to missing property in MW 1.29-
		if ( property_exists( $this->updater, 'maintenance' ) ) {
			$reflectionProperty = new ReflectionProperty( $this->updater, 'maintenance' );
			$reflectionProperty->setAccessible( true );
			$maintenance = $reflectionProperty->getValue( $this->updater );
		}

		if ( $maintenance instanceof Maintenance ) {
			$reflectionProperty = new ReflectionProperty( $maintenance, 'mOptions' );
			$reflectionProperty->setAccessible( true );
			$options = $reflectionProperty->getValue( $maintenance );
			return isset( $options[$key] );
		}

		return false;
	}

}
