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
 * Also registers the parse with `Outputs` so that a nested parse does not
 * drain the resource-module buffer of an enclosing parse, see #7009.
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
		// `Outputs` and the in-flight-parse tracker both apply their own
		// filtering, so it is safe to notify them for every parser here.
		Outputs::onParseStart( $parser );
		ParserAfterTidy::onParserClearState( $parser );

		return true;
	}

}
