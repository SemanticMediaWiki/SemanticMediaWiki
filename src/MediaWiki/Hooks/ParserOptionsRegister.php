<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\ParserOptionsRegisterHook;

/**
 * Registers SMW-specific parser options keys.
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserOptionsRegister
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class ParserOptionsRegister implements ParserOptionsRegisterHook {

	/**
	 * @since 7.0.0
	 */
	public function onParserOptionsRegister( &$defaults, &$inCacheKey, &$lazyLoad ) {
		// #2509
		// Register a new options key, used in connection with #ask/#show
		// where the use of a localTime invalidates the ParserCache to avoid
		// stalled settings for users with different preferences
		$defaults['localTime'] = false;
		$inCacheKey['localTime'] = true;

		return true;
	}

}
