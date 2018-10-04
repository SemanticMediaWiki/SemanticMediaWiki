<?php

namespace SMW\MediaWiki\Hooks;

/**
 * Called when generating the extensions credits, use this to change the tables headers
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ExtensionTypes
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class ExtensionTypes extends HookHandler {

	/**
	 * @since 2.0
	 *
	 * @param array $extensionTypes
	 *
	 * @return boolean
	 */
	public function process( array &$extensionTypes ) {

		if ( !is_array( $extensionTypes ) ) {
			$extensionTypes = [];
		}

		$extensionTypes = array_merge(
			[ 'semantic' => wfMessage( 'version-semantic' )->text() ],
			$extensionTypes
		);

		return true;
	}

}
