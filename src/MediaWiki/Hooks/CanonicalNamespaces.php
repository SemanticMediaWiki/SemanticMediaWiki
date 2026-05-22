<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\CanonicalNamespacesHook;
use SMW\NamespaceManager;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/CanonicalNamespaces
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class CanonicalNamespaces implements CanonicalNamespacesHook {

	/**
	 * @since 7.0.0
	 */
	public function onCanonicalNamespaces( &$namespaces ): void {
		NamespaceManager::initCanonicalNamespaces( $namespaces );
	}

}
