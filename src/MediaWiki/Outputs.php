<?php

namespace SMW\MediaWiki;

use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use WeakMap;

/**
 * This class attempts to provide safe yet simple means for managing data that is relevant
 * for the final HTML output of MediaWiki. In particular, this concerns additions to the HTML
 * header in the form of scripts or stylesheets.
 *
 * The problem is that many components in SMW create hypertext that should eventually be displayed.
 * The normal way of accessing such text are functions of the form getText() which return a
 * (hypertext) string. Such a string, however, might refer to styles or scripts that are also
 * needed. It is not possible to directly add those scripts to the MediaWiki output, since the form
 * of this output depends on the context in which the function is called. Many functions might be
 * called both during parsing and directly in special pages that do not use the usual parsing and
 * caching mechanisms.
 *
 * Ideally, all functions that generate hypertext with dependencies would also include parameters to
 * record required scripts. Since this would require major API changes, the current solution is to have
 * a "temporal" global storage for the required items, managed in this class. It is not safe to use
 * such a global store across hooks -- you never know what happens in between! Hence, every function
 * that creates SMW outputs that may require head items must afterwards clear the temporal store by
 * writing its contents to the according output.
 *
 * @ingroup SMW
 *
 * @author Markus Krötzsch
 */
class Outputs {

	/**
	 * Protected member for temporarily storing header items.
	 * Format $id => $headItem where $id is used only to avoid duplicate
	 * items in the time before they are forwarded to the output.
	 */
	protected static array $headItems = [];

	/**
	 * Protected member for temporarily storing additional Javascript
	 * snippets. Format $id => $scriptText where $id is used only to
	 * avoid duplicate scripts in the time before they are forwarded
	 * to the output.
	 */
	protected static array $scripts = [];

	/**
	 * Protected member for temporarily storing resource modules.
	 */
	protected static array $resourceModules = [];

	/**
	 * Protected member for temporarily storing resource modules.
	 */
	protected static array $resourceStyles = [];

	/**
	 * Protected member for temporarily storing JavaScript Configuration Variables
	 */
	protected static array $jsConfigVars = [];

	/**
	 * Top-level content parses (`Parser::parse()` with output type HTML that
	 * are not interface-message parses) currently in progress, keyed by the
	 * `Parser` instance running them. Populated by `onParseStart()` (via the
	 * `ParserClearState` hook) and drained by `onParseEnd()` (via the
	 * `ParserAfterTidy` hook).
	 *
	 * It exists so that `commitToParserOutput()` can tell whether it is running
	 * inside a nested parse: when an extension such as DynamicPageList starts a
	 * second `Parser::parse()` while an outer `{{#ask}}` has already registered
	 * modules, the inner commit must not drain the buffer, or those modules are
	 * lost when the inner `ParserOutput` is discarded (see #7009).
	 *
	 * A `WeakMap` keyed by `Parser` is used so that entries are reclaimed by GC
	 * and, crucially, so membership can be validated against `Parser::isLocked()`:
	 * a parser that has left `parse()` (normally or via an exception, which has
	 * no `ParserAfterTidy`) reports `isLocked() === false` and is never counted,
	 * making the nesting check self-healing rather than a drift-prone counter.
	 */
	private static ?WeakMap $activeParses = null;

	/**
	 * Adds a resource module to the parser output.
	 *
	 * @since 1.5.3
	 */
	public static function requireResource( string $moduleName ): void {
		self::$resourceModules[$moduleName] = $moduleName;
	}

	/**
	 * @since 3.0
	 */
	public static function requireStyle( string $stylesName ): void {
		self::$resourceStyles[$stylesName] = $stylesName;
	}

	/**
	 * Require a JS config var so it will be added via
	 * ParserOutput::setJsConfigVar or OutputPage::addJsConfigVars
	 *
	 * @param string $key Key to use under mw.config
	 * @param mixed|null $value
	 */
	public static function requireJsConfigVar( string $key, $value ): void {
		self::$jsConfigVars[$key] = $value;
	}

	/**
	 * Called from the `ParserClearState` hook at the start of every
	 * `Parser::parse()` invocation. Registers the parser as an in-progress
	 * top-level content parse so that a later, nested parse can be recognised
	 * by `commitToParserOutput()`.
	 *
	 * Only genuine top-level content parses are tracked. The `isLocked()` check
	 * comes first: `Parser::clearState()` also fires outside a real parse (test
	 * helpers, manual state resets), and such a parser is neither locked nor has
	 * it initialised its output type yet, so the output type must not be read
	 * for it. `preprocess()` and `getPreloadText()` are locked but run with a
	 * non-HTML output type and never reach `ParserAfterTidy`, and interface-
	 * message parses (`Message::parse()`) must not influence the buffer
	 * lifecycle of a surrounding special-page render; both are excluded.
	 *
	 * @since 7.1.0
	 */
	public static function onParseStart( Parser $parser ): void {
		if ( !$parser->isLocked()
			|| $parser->getOutputType() !== Parser::OT_HTML
			|| $parser->getOptions()->getInterfaceMessage() ) {
			return;
		}

		self::$activeParses ??= new WeakMap();
		self::$activeParses[$parser] = true;
	}

	/**
	 * Called from the `ParserAfterTidy` hook at the end of every
	 * `Parser::parse()` invocation. Unregisters the parser from the
	 * in-progress set. No buffer clearing happens here; the buffers are
	 * cleared by the outermost `commitToParserOutput()` (see there).
	 *
	 * @since 7.1.0
	 */
	public static function onParseEnd( Parser $parser ): void {
		if ( self::$activeParses !== null ) {
			unset( self::$activeParses[$parser] );
		}
	}

	/**
	 * Whether a `commitToParserOutput()` call is happening inside a nested
	 * parse, i.e. an enclosing top-level content parse is still locked while
	 * an inner parse commits. Stale entries whose parser is no longer locked
	 * (e.g. left `parse()` via an exception) are ignored, so the check cannot
	 * drift out of balance.
	 */
	private static function isNestedParse(): bool {
		if ( self::$activeParses === null ) {
			return false;
		}

		$locked = 0;
		foreach ( self::$activeParses as $parser => $unused ) {
			if ( $parser->isLocked() && ++$locked > 1 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Reset the in-progress parse registry and all output buffers.
	 * Intended for tests.
	 *
	 * @since 7.1.0
	 */
	public static function reset(): void {
		self::$activeParses = null;
		self::$resourceStyles = [];
		self::$resourceModules = [];
		self::$headItems = [];
		self::$scripts = [];
		self::$jsConfigVars = [];
	}

	/**
	 * Require the presence of header scripts, provided as strings with
	 * enclosing script tags. Note that the same could be achieved with
	 * requireHeadItems, but scripts use a special method "addScript" in
	 * MediaWiki OutputPage, hence we distinguish them.
	 *
	 * The id is used to avoid that the requirement for one script is
	 * recorded multiple times in Outputs.
	 */
	public static function requireScript( string $id, string $script ): void {
		self::$scripts[$id] = $script;
	}

	/**
	 * Adds head items that are not Resource Loader modules. Should only
	 * be used for custom head items such as RSS feed links.
	 *
	 * The id is used to avoid that the requirement for one script is
	 * recorded multiple times in Outputs.
	 */
	public static function requireHeadItem( string $id, string $item = '' ): void {
		self::$headItems[$id] = $item;
	}

	/**
	 * This function takes output requirements as can be found in a given ParserOutput
	 * object and puts them back in to the internal temporal requirement list from
	 * which they can be committed to some other output. It is needed when code that
	 * would normally call Outputs::requireHeadItem() has need to use a full
	 * independent parser call (Parser::parse()) that produces its own parseroutput.
	 * If omitted, all output items potentially committed to this parseroutput during
	 * parsing will not be passed on to higher levels.
	 *
	 * Note that this is not required if the $parseroutput is further processed by
	 * MediaWiki, but there are cases where the output is discarded and only its text
	 * is used.
	 */
	public static function requireFromParserOutput( ParserOutput $parserOutput ): void {
		// Note: we do not attempt to recover which head items where scripts here.

		$parserOutputHeadItems = $parserOutput->getHeadItems();

		self::$headItems = array_merge( self::$headItems, $parserOutputHeadItems );

		/// TODO Is the following needed?
		if ( $parserOutput->getModules() ) {
			foreach ( $parserOutput->getModules() as $module ) {
				self::$resourceModules[$module] = $module;
			}
		}

		self::$jsConfigVars = array_merge( self::$jsConfigVars, $parserOutput->getJsConfigVars() ?? [] );
	}

	/**
	 * Actually commit the collected requirements to a given parser that is about to parse
	 * what will later be the HTML output. This makes sure that HTML output based on the
	 * parser results contains all required output items.
	 *
	 * If the parser creates output for a normal wiki page, then the committed items will
	 * also become part of the page cache so that they will correctly be added to all page
	 * outputs built from this cache later on.
	 */
	public static function commitToParser( Parser $parser ): void {
		$po = $parser->getOutput();
		self::commitToParserOutput( $po );
	}

	/**
	 * Similar to Outputs::commitToParser() but acting on a ParserOutput object.
	 *
	 * The buffers are cleared after committing, except while an enclosing
	 * parse is still in progress. During a nested parse (e.g. one started by
	 * DynamicPageList while an outer `{{#ask}}` has already registered modules)
	 * the inner commit preserves the buffer so that the outermost ParserOutput
	 * still receives those modules instead of losing them to the discarded
	 * inner ParserOutput (see #7009). A commit made outside of any parse (a
	 * special page calling commitToParser directly) counts as outermost and
	 * clears normally.
	 */
	public static function commitToParserOutput( ParserOutput $parserOutput ): void {
		foreach ( self::$scripts as $key => $script ) {
			$parserOutput->addHeadItem( $script . "\n", $key );
		}

		foreach ( self::$headItems as $key => $item ) {
			$parserOutput->addHeadItem( "\t\t" . $item . "\n", $key );
		}

		$parserOutput->addModuleStyles( array_values( self::$resourceStyles ) );
		$parserOutput->addModules( array_values( self::$resourceModules ) );

		foreach ( self::$jsConfigVars as $key => $value ) {
			$parserOutput->setJsConfigVar( $key, $value );
		}

		if ( !self::isNestedParse() ) {
			self::$resourceStyles = [];
			self::$resourceModules = [];
			self::$headItems = [];
			self::$scripts = [];
			self::$jsConfigVars = [];
		}
	}

	/**
	 * Actually commit the collected requirements to a given OutputPage object that
	 * will later generate the HTML output. This makes sure that HTML output contains
	 * all required output items. Note that there is no parser caching at this level of
	 * processing. In particular, data should not be committed to $wgOut in methods
	 * that run during page parsing, since these would not run next time when the page
	 * is produced from parser cache.
	 */
	public static function commitToOutputPage( OutputPage $output ): void {
		foreach ( self::$scripts as $script ) {
			$output->addScript( $script );
		}
		foreach ( self::$headItems as $key => $item ) {
			$output->addHeadItem( $key, "\t\t" . $item . "\n" );
		}

		$output->addModuleStyles( array_values( self::$resourceStyles ) );
		$output->addModules( array_values( self::$resourceModules ) );

		$output->addJsConfigVars( self::$jsConfigVars );

		self::$resourceStyles = [];
		self::$resourceModules = [];
		self::$headItems = [];
		self::$scripts = [];
		self::$jsConfigVars = [];
	}

}

/**
 * @deprecated since 7.0.0
 */
class_alias( Outputs::class, 'SMWOutputs' );
