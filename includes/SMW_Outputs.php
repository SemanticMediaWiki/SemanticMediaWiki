<?php

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
 * @file SMW_Ouputs.php
 * @ingroup SMW
 *
 * @author Markus Krötzsch
 */
class SMWOutputs {

	/**
	 * Protected member for temporarily storing header items.
	 * Format $id => $headItem where $id is used only to avoid duplicate
	 * items in the time before they are forwarded to the output.
	 */
	protected static $headItems = array();

	/**
	 * Protected member for temporarily storing additional Javascript
	 * snippets. Format $id => $scriptText where $id is used only to
	 * avoid duplicate scripts in the time before they are forwarded
	 * to the output.
	 */
	protected static $scripts = array();
	
	/// Protected member for temporarily storing resource modules.
	protected static $resourceModules = array();

	/**
	 * Adds a resource module to the parser output.
	 * 
	 * @since 1.5.3
	 * 
	 * @param string $moduleName
	 */
	public static function requireResource( $moduleName ) {
		self::$resourceModules[$moduleName] = $moduleName;
	}

	/**
	 * Require the presence of header scripts, provided as strings with
	 * enclosing script tags. Note that the same could be achieved with
	 * requireHeadItems, but scripts use a special method "addScript" in
	 * MediaWiki OutputPage, hence we distinguish them.
	 * 
	 * The id is used to avoid that the requirement for one script is
	 * recorded multiple times in SMWOutputs.
	 * 
	 * @param string $id
	 * @param string $item
	 */
	public static function requireScript( $id, $script ) {
		self::$scripts[$id] = $script;
	}
	
	/**
	 * Adds head items that are not Resource Loader modules. Should only
	 * be used for custom head items such as RSS fedd links.
	 *
	 * The id is used to avoid that the requirement for one script is
	 * recorded multiple times in SMWOutputs.
	 *
	 * Support for calling this with the old constants SMW_HEADER_STYLE
	 * and SMW_HEADER_TOOLTIP will vanish in SMW 1.7 at the latest.
	 * 
	 * @param mixed $id
	 * @param string $item
	 */
	public static function requireHeadItem( $id, $item = '' ) {
		if ( is_numeric( $id ) ) {
			switch ( $id ) {
				case SMW_HEADER_TOOLTIP:
					self::requireResource( 'ext.smw.tooltips' );
				break;
				case SMW_HEADER_STYLE:
					self::requireResource( 'ext.smw.style' );
				break;
			}
		} else {
			self::$headItems[$id] = $item;
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
		// Note: we do not attempt to recover which head items where scripts here.

		$parserOutputHeadItems = $parserOutput->getHeadItems();

		self::$headItems = array_merge( (array)self::$headItems, $parserOutputHeadItems );

		/// TODO Is the following needed?
		if ( isset( $parserOutput->mModules ) ) {
			foreach ( $parserOutput->mModules as $module ) {
				self::$resourceModules[$module] = $module;
			}
		}
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
		/// TODO find out and document when this b/c code can go away
		if ( method_exists( $parser, 'getOutput' ) ) {
			$po = $parser->getOutput();
		} else {
			$po = $parser->mOutput;
		}

		if ( isset( $po ) ) {
			self::commitToParserOutput( $po );
		}
	}

	/**
	 * Similar to SMWOutputs::commitToParser() but acting on a ParserOutput object.
	 *
	 * @param ParserOutput $parserOutput
	 */
	static public function commitToParserOutput( ParserOutput $parserOutput ) {
		foreach ( self::$scripts as $key => $script ) {
			$parserOutput->addHeadItem( $script . "\n", $key );
		}
		foreach ( self::$headItems as $key => $item ) {
			$parserOutput->addHeadItem( "\t\t" . $item . "\n", $key );
		}

		$parserOutput->addModules( array_values( self::$resourceModules ) );

		self::$resourceModules = array();
		self::$headItems = array();
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
		foreach ( self::$scripts as $script ) {
			$output->addScript( $script );
		}
		foreach ( self::$headItems as $key => $item ) {
			$output->addHeadItem( $key, "\t\t" . $item . "\n" );
		}

		$output->addModules( array_values( self::$resourceModules ) );

		self::$resourceModules = array();
		self::$headItems = array();
	}

}
