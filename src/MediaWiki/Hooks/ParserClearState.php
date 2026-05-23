<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\ParserClearStateHook;

/**
 * Hook: ParserClearState fires at the start of every `Parser::parse()`
 * call. Used to track in-flight parses per title so that `ParserAfterTidy`
 * can distinguish the outermost fire from inner (nested) fires triggered
 * by extensions that clone the parser, see #5923.
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserClearState
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class ParserClearState implements ParserClearStateHook {

	/**
	 * @since 7.0.0
	 */
	public function onParserClearState( $parser ) {
		ParserAfterTidy::onParserClearState( $parser );

		return true;
	}

}
