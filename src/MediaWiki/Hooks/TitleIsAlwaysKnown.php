<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\TitleIsAlwaysKnownHook;
use SMW\DataItems\Property;

/**
 * Allows overriding default behaviour for determining if a page exists
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleIsAlwaysKnown
 *
 * @license GPL-2.0-or-later
 * @since 2.0
 *
 * @author mwjames
 */
class TitleIsAlwaysKnown implements TitleIsAlwaysKnownHook {

	/**
	 * @since 7.0.0
	 */
	public function onTitleIsAlwaysKnown( $title, &$isKnown ) {
		// Two possible ways of going forward:
		//
		// The FIRST seen here is to use the hook to override the known status
		// for predefined properties in order to avoid any edit link
		// which makes no-sense for predefined properties
		//
		// The SECOND approach is to inject WikiPageValue with a setLinkOptions setter
		// that enables to set the custom options 'known' for each invoked linker during
		// getShortHTMLText
		// $linker->link( $this->getTitle(), $caption, $customAttributes, $customQuery, $customOptions )
		//
		// @see also HooksTest::testOnTitleIsAlwaysKnown

		if ( $title->getNamespace() === SMW_NS_PROPERTY ) {
			if ( !Property::newFromUserLabel( $title->getText() )->isUserDefined() ) {
				$isKnown = true;
			}
		}

		return true;
	}

}
