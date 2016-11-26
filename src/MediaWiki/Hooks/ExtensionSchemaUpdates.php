<?php

namespace SMW\MediaWiki\Hooks;

use DatabaseUpdater;

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
		$isFromExtensionSchemaUpdate = true;

		// Needs a static caller otherwise the DatabaseUpdater returns with:
		// "Warning: call_user_func_array() expects parameter 1 to be a
		// valid callback ..."
		$this->updater->addExtensionUpdate( array( 'SMWStore::setupStore', array(
			$verbose,
			$isFromExtensionSchemaUpdate
		) ) );

		return true;
	}

}
