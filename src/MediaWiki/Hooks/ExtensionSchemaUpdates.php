<?php

namespace SMW\MediaWiki\Hooks;

use DatabaseUpdater;
use SMW\SQLStore\Installer;
use SMW\Options;

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
				Installer::OPT_TABLE_OPTIMZE => true,
				Installer::OPT_IMPORT => true
			]
		);

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

}
