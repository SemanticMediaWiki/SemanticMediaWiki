<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\TitleIsMovableHook;
use SMW\DataItems\Property;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleIsMovable
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class TitleIsMovable implements TitleIsMovableHook {

	/**
	 * @since 7.0.0
	 */
	public function onTitleIsMovable( $title, &$result ) {
		// We don't allow rule pages to be moved as we cannot track JSON content
		// as redirects and therefore invalidate any rule assignment without a
		// possibility to automatically reassign IDs
		if ( $title->getNamespace() === SMW_NS_SCHEMA ) {
			$result = false;
		}

		if ( $title->getNamespace() !== SMW_NS_PROPERTY ) {
			return true;
		}

		// Predefined properties cannot be moved!
		if ( !Property::newFromUserLabel( $title->getText() )->isUserDefined() ) {
			$result = false;
		}

		return true;
	}

}
