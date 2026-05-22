<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\ExtensionTypesHook;

/**
 * Called when generating the extensions credits, use this to change the tables headers
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ExtensionTypes
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class ExtensionTypes implements ExtensionTypesHook {

	/**
	 * @since 7.0.0
	 */
	public function onExtensionTypes( &$extTypes ) {
		$extTypes = array_merge(
			[ 'semantic' => wfMessage( 'version-semantic' )->text() ],
			$extTypes
		);

		return true;
	}

}
