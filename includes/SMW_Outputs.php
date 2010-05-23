<?php
/**
 * This file contains the SMWOutputs class.
 *
 * @author Markus Krötzsch
 *
 * @file
 * @ingroup SMW
 */

/**
 * This class attempts to provide safe yet simple means for managing data that is relevant
 * for the final HTML output of MediaWiki. In particular, this concerns additions to the HTML
 * header in the form of scripts of stylesheets.
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
 * such a global store accross hooks -- you never know what happens in between! Hence, every function
 * that creates SMW outputs that may require head items must afterwards clear the temporal store by
 * writing its contents to the according output.
 *
 * @ingroup SMW
 */
class SMWOutputs {

	/// Protected member function for temporarily storing header items
	static protected $mHeadItems = array();

	/**
	 * Announce that some head item (usually CSS or JavaScript) is required to
	 * display the content just created. The function is called with an ID that
	 * is one of SMW's SMW_HEADER_... constants, or a string ID followed by the
	 * actual item that should be added to the output HTML header. In the first
	 * case, the $item parameter should be left unspecified.
	 *
	 * @note This function does not actually add anything to the output yet.
	 * This happens only by calling SMWOutputs::commitToParserOutput(),
	 * SMWOutputs::commitToOutputPage(), or SMWOutputs::commitToParser(). Virtually
	 * every function that eventually produces HTML text output using SMW functions
	 * must take care of calling one of those committing functions before passing
	 * on control. It is not safe to commit later, e.g. in a hook that is expected
	 * to be called "soon" -- there might always be other hooks first that commit the
	 * existing data wrongly, depending on installed extensions and background jobs!
	 *
	 * @param $id string or predefined constant for identifying a head item
	 * @param $item string containing a complete HTML-compatibly text snippet that
	 * should go into the HTML header; only required if $id is no built-in constant.
	 */
	static public function requireHeadItem( $id, $item = '' ) {
		if ( is_numeric( $id ) ) {
			global $smwgScriptPath;

			switch ( $id ) {
				case SMW_HEADER_TOOLTIP:
					self::requireHeadItem( SMW_HEADER_STYLE );
					self::$mHeadItems['smw_tt'] = '<script type="text/javascript" src="' . $smwgScriptPath .  '/skins/SMW_tooltip.js"></script>';
				break;
				case SMW_HEADER_SORTTABLE:
					self::requireHeadItem( SMW_HEADER_STYLE );
					self::$mHeadItems['smw_st'] = '<script type="text/javascript" src="' . $smwgScriptPath .  '/skins/SMW_sorttable.js"></script>';
				break;
				case SMW_HEADER_STYLE:
					global $wgContLang;

					self::$mHeadItems['smw_css'] = '<link rel="stylesheet" type="text/css" href="' . $smwgScriptPath . '/skins/SMW_custom.css" />';

					if ( $wgContLang->isRTL() ) { // right-to-left support
						self::$mHeadItems['smw_cssrtl'] = '<link rel="stylesheet" type="text/css" href="' . $smwgScriptPath . '/skins/SMW_custom_rtl.css" />';
					}
				break;
			}
		} else { // custom head item
			self::$mHeadItems[$id] = $item;
		}
	}

	/**
	 * This function takes output requirements as can be found in a given ParserOutput
	 * object and puts them back in to the internal temporal requirement list from
	 * which they can be committed to some other output. It is needed when code that
	 * would normally call SMWOutputs::requireHeadItem() has need to use a full
	 * independent parser call (Parser::parse()) that produces its own parseroutput.
	 * If omitted, all output items potentially committed to this parseroutput during
	 * parsing will not be passed on to higher levels.
	 *
	 * Note that this is not required if the $parseroutput is further processed by
	 * MediaWiki, but there are cases where the output is discarded and only its text
	 * is used.
	 *
	 * @param ParserOutput $parserOutput
	 */
	static public function requireFromParserOutput( ParserOutput $parserOutput ) {
		self::$mHeadItems = array_merge( (array)self::$mHeadItems, (array)$parserOutput->mHeadItems );
	}

	/**
	 * Acutally commit the collected requirements to a given parser that is about to parse
	 * what will later be the HTML output. This makes sure that HTML output based on the
	 * parser results contains all required output items.
	 *
	 * If the parser creates output for a normal wiki page, then the committed items will
	 * also become part of the page cache so that they will correctly be added to all page
	 * outputs built from this cache later on.
	 *
	 * @param Parser $parser
	 */
	static public function commitToParser( Parser $parser ) {
		if ( method_exists( $parser, 'getOutput' ) ) {
			$po = $parser->getOutput();
		} else {
			$po = $parser->mOutput;
		}

		if ( isset( $po ) ) self::commitToParserOutput( $po );
	}

	/**
	 * Similar to SMWOutputs::commitToParser() but acting on a ParserOutput object.
	 *
	 * @param ParserOutput $parserOutput
	 */
	static public function commitToParserOutput( ParserOutput $parserOutput ) {
		// debug_zval_dump(self::$mItems);
		foreach ( self::$mHeadItems as $key => $item ) {
			$parserOutput->addHeadItem( "\t\t" . $item . "\n", $key );
		}

		self::$mHeadItems = array();
	}

	/**
	 * Acutally commit the collected requirements to a given OutputPage object that
	 * will later generate the HTML output. This makes sure that HTML output contains
	 * all required output items. Note that there is no parser caching at this level of
	 * processing. In particular, data should not be committed to $wgOut in methods
	 * that run during page parsing, since these would not run next time when the page
	 * is produced from parser cache.
	 *
	 * @param OutputPage $output
	 */
	static public function commitToOutputPage( OutputPage $output ) {
		foreach ( self::$mHeadItems as $key => $item ) {
			$output->addHeadItem( $key, "\t\t" . $item . "\n" );
		}

		self::$mHeadItems = array();
	}
}