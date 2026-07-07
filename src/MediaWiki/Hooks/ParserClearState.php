<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\ParserClearStateHook;
use SMW\MediaWiki\Outputs;

/**
 * Hook: ParserClearState fires at the start of every `Parser::parse()`
 * call. Used to track in-flight parses per title so that `ParserAfterTidy`
 * can distinguish the outermost fire from inner (nested) fires triggered
 * by extensions that clone the parser, see #5923.
 *
 * Also increments the global parse-depth counter in `Outputs` so that
 * nested parses do not drain the resource-module buffer prematurely.
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
		// Depth counter must be incremented unconditionally because
		// ParserAfterTidy (where it is decremented) fires for every
		// Parser::parse() call. The in-flight-parse tracker has its
		// own guards and can safely be called for all parsers too.
		Outputs::onParseStart();
		ParserAfterTidy::onParserClearState( $parser );

		return true;
	}

}
