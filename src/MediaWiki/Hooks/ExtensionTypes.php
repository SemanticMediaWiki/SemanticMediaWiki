<?php

namespace SMW\MediaWiki\Hooks;

use SMW\MediaWiki\HookListener;

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
class ExtensionTypes implements HookListener {

	/**
	 * @since 2.0
	 */
	public function process( array &$extensionTypes ): bool {
		$extensionTypes = array_merge(
			[ 'semantic' => wfMessage( 'version-semantic' )->text() ],
			$extensionTypes
		);

		return true;
	}

}
