<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\MaintenanceUpdateAddParamsHook;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/MaintenanceUpdateAddParams
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class MaintenanceUpdateAddParams implements MaintenanceUpdateAddParamsHook {

	/**
	 * @since 7.0.0
	 */
	public function onMaintenanceUpdateAddParams( &$params ) {
		ExtensionSchemaUpdates::addMaintenanceUpdateParams( $params );

		return true;
	}

}
