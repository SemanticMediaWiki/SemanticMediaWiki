<?php

namespace SMW\MediaWiki\Hooks;

use DatabaseUpdater;
use Maintenance;
use ReflectionProperty;
use SMW\Options;
use SMW\Store;
use SMW\SQLStore\Installer;
use SMW\StoreFactory;
use Onoi\MessageReporter\MessageReporterFactory;
use SMW\MediaWiki\HookListener;

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
class ExtensionSchemaUpdates implements HookListener {

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
	 * @since 3.1
	 *
	 * @param array $params
	 */
	public static function addMaintenanceUpdateParams( &$params ) {

		// For details, see https://github.com/wikimedia/mediawiki/commit/a6facc8a0a4f9b54e0cfb1e5ef6f3991de752342
		$params['skip-optimize'] = [
			'desc' => 'SMW, allow to skip the table optimization during the Store setup'
		];
	}

	/**
	 * @since 2.0
	 *
	 * @param Store $store
	 *
	 * @return true
	 */
	public function process( Store $store ) {

		$verbose = true;

		$options = new Options(
			[
				Installer::OPT_TABLE_OPTIMIZE => true,
				Installer::RUN_IMPORT => true,
				Installer::OPT_SUPPLEMENT_JOBS => true,
				'verbose' => $verbose
			]
		);

		if ( $this->hasMaintenanceArg( 'skip-optimize' ) ) {
			$options->set( Installer::OPT_TABLE_OPTIMIZE, false );
		}

		$messageReporterFactory = new MessageReporterFactory();

		$messageReporter = $messageReporterFactory->newObservableMessageReporter();
		$messageReporter->registerReporterCallback( [ $this->updater, 'output' ] );

		// Injecting `MessageReporter` here to avoid "Database serialization may
		// cause problems, since the connection is not restored on wakeup." given
		// that the `DatabaseUpdater` prior MW 1.31 has issues with serializing
		// the options array.
		$store->setMessageReporter( $messageReporter );

		if ( defined( 'MW_UPDATER' ) ) {
			$options->set( SMW_EXTENSION_SCHEMA_UPDATER, true );
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
