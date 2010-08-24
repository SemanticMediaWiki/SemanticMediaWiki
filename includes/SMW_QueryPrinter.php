<?php
/**
 * File with abstract base class for printing query results.
 * @author Markus Krötzsch
 * @file
 * @ingroup SMWQuery
 */

// constants that define how/if headers should be displayed
define( 'SMW_HEADERS_SHOW', 2 );
define( 'SMW_HEADERS_PLAIN', 1 );
define( 'SMW_HEADERS_HIDE', 0 ); // used to be "false" hence use "0" to support extensions that still assume this

/**
 * Abstract base class for SMW's novel query printing mechanism. It implements
 * part of the former functionality of SMWInlineQuery (everything related to
 * output formatting and the correspoding parameters) and is subclassed by concrete
 * printers that provide the main formatting functionality.
 * @ingroup SMWQuery
 */
abstract class SMWResultPrinter {

	protected $m_params;

	/** Text to print *before* the output in case it is *not* empty; assumed to be wikitext.
	  * Normally this is handled in SMWResultPrinter and can be ignored by subclasses. */
	protected $mIntro = '';

	/** Text to print *after* the output in case it is *not* empty; assumed to be wikitext.
	  * Normally this is handled in SMWResultPrinter and can be ignored by subclasses. */
	protected $mOutro = '';

	/** Text to use for link to further results, or empty if link should not be shown.
	 *  Unescaped! Use SMWResultPrinter::getSearchLabel() and SMWResultPrinter::linkFurtherResults()
	 *  instead of accessing this directly. */
	protected $mSearchlabel = null;

	/** Default return value for empty queries. Unescaped. Normally not used in sub-classes! */
	protected $mDefault = '';

	// parameters relevant for printers in general:
	protected $mFormat;  // a string identifier describing a valid format
	protected $mLinkFirst; // should article names of the first column be linked?
	protected $mLinkOthers; // should article names of other columns (besides the first) be linked?
	protected $mShowHeaders = SMW_HEADERS_SHOW; // should the headers (property names) be printed?
	protected $mShowErrors = true; // should errors possibly be printed?
	protected $mInline; // is this query result "inline" in some page (only then a link to unshown results is created, error handling may also be affected)
	protected $mLinker; // Linker object as needed for making result links. Might come from some skin at some time.

	/**
	 * If set, treat result as plain HTML. Can be used by printer classes if wiki mark-up is not enough.
	 * This setting is used only after the result text was generated.
	 * @note HTML query results cannot be used as parameters for other templates or in any other way
	 * in combination with other wiki text. The result will be inserted on the page literally.
	 */
	protected $isHTML = false;

	/**
	 * If set, take the necessary steps to make sure that things like {{templatename| ...}} are properly
	 * processed if they occur in the result. Clearly, this is only relevant if the output is not HTML, i.e.
	 * it is ignored if SMWResultPrinter::$is_HTML is true. This setting is used only after the result
	 * text was generated.
	 * @note This requires extra processing and may make the result less useful for being used as a
	 * parameter for further parser functions. Use only if required.
	 */
	protected $hasTemplates = false;
	/// Incremented while expanding templates inserted during printout; stop expansion at some point
	private static $mRecursionDepth = 0;
	/// This public variable can be set to higher values to allow more recursion; do this at your own risk!
	/// This can be set in LocalSettings.php, but only after enableSemantics().
	public static $maxRecursionDepth = 2;

	/**
	 * Return serialised results in specified format.
	 * Implemented by subclasses.
	 */
	abstract protected function getResultText( /* SMWQueryResult */ $res, $outputmode );	
	
	/**
	 * Constructor. The parameter $format is a format string
	 * that may influence the processing details.
	 */
	public function SMWResultPrinter( $format, $inline ) {
		global $smwgQDefaultLinking;
		$this->mFormat = $format;
		$this->mInline = $inline;
		$this->mLinkFirst = ( $smwgQDefaultLinking != 'none' );
		$this->mLinkOthers = ( $smwgQDefaultLinking == 'all' );
		$this->mLinker = new Linker(); ///TODO: how can we get the default or user skin here (depending on context)?
	}

	/**
	 * Main entry point: takes an SMWQueryResult and parameters given as key-value-pairs in an array,
	 * and returns the serialised version of the results, formatted as HTML or Wiki or whatever is
	 * specified. Normally this is not overwritten by subclasses.
	 *
	 * If the outputmode is SMW_OUTPUT_WIKI, then the function will return something that is suitable
	 * for being used in a MediaWiki parser function, i.e. a wikitext strong *or* an array with flags
	 * and the string as entry 0. See Parser::setFunctionHook() for documentation on this. In all other
	 * cases, the function returns just a string.
	 *
	 * For outputs SMW_OUTPUT_WIKI and SMW_OUTPUT_HTML, error messages or standard "further results" links
	 * are directly generated and appended. For SMW_OUTPUT_FILE, only the plain generated text is returned.
	 *
	 * @note A note on recursion: some query printers may return wiki code that comes from other pages,
	 * e.g. from templates that are used in formatting or from embedded result pages. Both kinds of pages
	 * may contain \#ask queries that do again use new pages, so we must care about recursion. We do so
	 * by simply counting how often this method starts a subparse and stopping at depth 2. There is one
	 * special case: if this method is called outside parsing, and the concrete printer returns wiki text,
	 * and wiki text is requested, then we may return wiki text with sub-queries to the caller. If the
	 * caller parses this (which is likely) then this will again call us in parse-context and all recursion
	 * checks catch. Only the first level of parsing is done outside and thus not counted. Thus you
	 * effectively can get down to level 3. The basic maximal depth of 2 can be changed by setting the
	 * variable SMWResultPrinter::$maxRecursionDepth (in LocalSettings.php, after enableSemantics()).
	 * Do this at your own risk.
	 * 
	 * @param SMWQueryResult $results
	 * @param array $params
	 * @param $outputmode
	 * 
	 * @return string
	 */
	public function getResult( $results, $params, $outputmode ) {
		global $wgParser;
		
		$this->isHTML = false;
		$this->hasTemplates = false;
		$this->readParameters( $params, $outputmode );

		// Default output for normal printers:
		if ( ( $outputmode != SMW_OUTPUT_FILE ) && // not in FILE context,
		     ( $results->getCount() == 0 ) && // no results,
		     ( $this->getMimeType( $results ) === false ) ) { // normal printer -> take over processing
			if ( !$results->hasFurtherResults() ) {
				return $this->escapeText( $this->mDefault, $outputmode ) . $this->getErrorString( $results );
			} elseif ( $this->mInline ) {
				$label = $this->mSearchlabel;
				
				if ( $label === null ) { // apply defaults
					smwfLoadExtensionMessages( 'SemanticMediaWiki' );
					$label = wfMsgForContent( 'smw_iq_moreresults' );
				}
				
				if ( $label != '' ) {
					$link = $results->getQueryLink( $this->escapeText( $label, $outputmode ) );
					$result = $link->getText( $outputmode, $this->mLinker );
				} else {
					$result = '';
				}
				
				$result .= $this->getErrorString( $results );
				
				return $result;
			}
		}

		// Get output from printer:
		$result = $this->getResultText( $results, $outputmode );
		
		if ( $outputmode == SMW_OUTPUT_FILE ) { // just return result in file mode
			return $result;
		}
		
		$result .= $this->getErrorString( $results ); // append errors
		
		if ( ( !$this->isHTML ) && ( $this->hasTemplates ) ) { // preprocess embedded templates if needed
			if ( ( $wgParser->getTitle() instanceof Title ) && ( $wgParser->getOptions() instanceof ParserOptions ) ) {
				SMWResultPrinter::$mRecursionDepth++;
				
				if ( SMWResultPrinter::$mRecursionDepth <= SMWResultPrinter::$maxRecursionDepth ) { // restrict recursion
					$result = '[[SMW::off]]' . $wgParser->replaceVariables( $result ) . '[[SMW::on]]';
				} else {
					$result = ''; /// TODO: explain problem (too much recursive parses)
				}
				
				SMWResultPrinter::$mRecursionDepth--;
			} else { // not during parsing, no preprocessing needed, still protect the result
				$result = '[[SMW::off]]' . $result . '[[SMW::on]]';
			}
		}

		if ( ( $this->isHTML ) && ( $outputmode == SMW_OUTPUT_WIKI ) ) {
			$result = array( $result, 'isHTML' => true );
		} elseif ( ( !$this->isHTML ) && ( $outputmode == SMW_OUTPUT_HTML ) ) {
			SMWResultPrinter::$mRecursionDepth++;
			
			// check whether we are in an existing parse, or if we should start a new parse for $wgTitle
			if ( SMWResultPrinter::$mRecursionDepth <= SMWResultPrinter::$maxRecursionDepth ) { // retrict recursion
				if ( ( $wgParser->getTitle() instanceof Title ) && ( $wgParser->getOptions() instanceof ParserOptions ) ) {
					$result = $wgParser->recursiveTagParse( $result );
				} else {
					global $wgTitle;
					
					$popt = new ParserOptions();
					$popt->setEditSection( false );
					$pout = $wgParser->parse( $result . '__NOTOC__', $wgTitle, $popt );
					
					/// NOTE: as of MW 1.14SVN, there is apparently no better way to hide the TOC
					SMWOutputs::requireFromParserOutput( $pout );
					$result = $pout->getText();
				}
			} else {
				$result = ''; /// TODO: explain problem (too much recursive parses)
			}
			
			SMWResultPrinter::$mRecursionDepth--;
		}

		if ( ( $this->mIntro ) && ( $results->getCount() > 0 ) ) {
			if ( $outputmode == SMW_OUTPUT_HTML ) {
				global $wgParser;
				$result = $wgParser->recursiveTagParse( $this->mIntro ) . $result;
			} else {
				$result = $this->mIntro . $result;
			}
		}

		if ( ( $this->mOutro ) && ( $results->getCount() > 0 ) ) {
			if ( $outputmode == SMW_OUTPUT_HTML ) {
				global $wgParser;
				$result = $result . $wgParser->recursiveTagParse( $this->mOutro );
			} else {
				$result = $result . $this->mOutro;
			}
		}

		return $result;
	}

	/**
	 * Read an array of parameter values given as key-value-pairs and
	 * initialise internal member fields accordingly. Possibly overwritten
	 * (extended) by subclasses.
	 * 
	 * @param array $params
	 * @param $outputmode
	 */
	protected function readParameters( /* array */ $params, $outputmode ) {
		$this->m_params = $params;
		
		if ( array_key_exists( 'intro', $params ) ) {
			$this->mIntro = str_replace( '_', ' ', $params['intro'] );
		}
		
		if ( array_key_exists( 'outro', $params ) ) {
			$this->mOutro = str_replace( '_', ' ', $params['outro'] );
		}
		
		if ( array_key_exists( 'searchlabel', $params ) ) {
			$this->mSearchlabel = $params['searchlabel'];
		}
		
		if ( array_key_exists( 'link', $params ) ) {
			switch ( strtolower( trim( $params['link'] ) ) ) {
			case 'head': case 'subject':
				$this->mLinkFirst = true;
				$this->mLinkOthers  = false;
				break;
			case 'all':
				$this->mLinkFirst = true;
				$this->mLinkOthers  = true;
				break;
			case 'none':
				$this->mLinkFirst = false;
				$this->mLinkOthers  = false;
				break;
			}
		}
		
		if ( array_key_exists( 'default', $params ) ) {
			$this->mDefault = str_replace( '_', ' ', $params['default'] );
		}
		
		if ( array_key_exists( 'headers', $params ) ) {
			if ( strtolower( trim( $params['headers'] ) ) == 'hide' ) {
				$this->mShowHeaders = SMW_HEADERS_HIDE;
			} elseif ( strtolower( trim( $params['headers'] ) ) == 'plain' ) {
				$this->mShowHeaders = SMW_HEADERS_PLAIN;
			} else {
				$this->mShowHeaders = SMW_HEADERS_SHOW;
			}
		}
	}

	/**
	 * Depending on current linking settings, returns a linker object
	 * for making hyperlinks or NULL if no links should be created.
	 *
	 * @param $firstcol True of this is the first result column (having special linkage settings).
	 */
	protected function getLinker( $firstcol = false ) {
		if ( ( $firstcol && $this->mLinkFirst ) || ( !$firstcol && $this->mLinkOthers ) ) {
			return $this->mLinker;
		} else {
			return null;
		}
	}

	/**
	 * Some printers do not mainly produce embeddable HTML or Wikitext, but
	 * produce stand-alone files. An example is RSS or iCalendar. This function
	 * returns the mimetype string that this file would have, or FALSE if no
	 * standalone files are produced.
	 *
	 * If this function returns something other than FALSE, then the printer will
	 * not be regarded as a printer that displays in-line results. This is used to
	 * determine if a file output should be generated in Special:Ask.
	 */
	public function getMimeType( $res ) {
		return false;
	}

	/**
	 * This function determines the query mode that is to be used for this printer in
	 * various contexts. The query mode influences how queries to that printer should
	 * be processed to obtain a result. Possible values are SMWQuery::MODE_INSTANCES
	 * (retrieve instances), SMWQuery::MODE_NONE (do nothing), SMWQuery::MODE_COUNT
	 * (get number of results), SMWQuery::MODE_DEBUG (return debugging text).
	 * Possible values for context are SMWQueryProcessor::SPECIAL_PAGE,
	 * SMWQueryProcessor::INLINE_QUERY, SMWQueryProcessor::CONCEPT_DESC.
	 *
	 * The default implementation always returns SMWQuery::MODE_INSTANCES. File exports
	 * like RSS will use MODE_INSTANCES on special pages (so that instances are
	 * retrieved for the export) and MODE_NONE otherwise (displaying just a download link).
	 */
	public function getQueryMode( $context ) {
		return SMWQuery::MODE_INSTANCES;
	}

	/**
	 * Some printers can produce not only embeddable HTML or Wikitext, but
	 * can also produce stand-alone files. An example is RSS or iCalendar.
	 * This function returns a filename that is to be sent to the caller
	 * in such a case (the default filename is created by browsers from the
	 * URL, and it is often not pretty).
	 *
	 * See also SMWResultPrinter::getMimeType()
	 */
	public function getFileName( $res ) {
		return false;
	}

	/**
	 * Get a human readable label for this printer. The default is to
	 * return just the format identifier. Concrete implementations may
	 * refer to messages here. The format name is normally not used in
	 * wiki text but only in forms etc. hence the user language should be
	 * used when retrieving messages.
	 * 
	 * @return string
	 */
	public function getName() {
		return $this->mFormat;
	}

	/**
	 * Provides a simple formatted string of all the error messages that occurred.
	 * Can be used if not specific error formatting is desired. Compatible with HTML
	 * and Wiki.
	 * 
	 * @return string
	 */
	public function getErrorString( $res ) {
		return $this->mShowErrors ? smwfEncodeMessages( $res->getErrors() ) : '';
	}

	/**
	 * Set whether errors should be shown. By default they are.
	 * 
	 * @param boolean $show
	 */
	public function setShowErrors( $show ) {
		$this->mShowErrors = $show;
	}

	/**
	 * If $outputmode is SMW_OUTPUT_HTML, escape special characters occuring in the
	 * given text. Otherwise return text as is.
	 * 
	 * @return string
	 */
	protected function escapeText( $text, $outputmode ) {
		return ( $outputmode == SMW_OUTPUT_HTML ) ? htmlspecialchars( $text ) : $text;
	}

	/**
	 * Get the string the user specified as a text for the "further results" link,
	 * properly escaped for the current output mode.
	 * 
	 * @return string
	 */
	protected function getSearchLabel( $outputmode ) {
		return $this->escapeText( $this->mSearchlabel, $outputmode );
	}

	/**
	 * Check whether a "further results" link would normally be generated for this
	 * result set with the given parameters. Individual result printers may decide to
	 * create or hide such a link independent of that, but this is the default.
	 * 
	 * @return boolean
	 */
	protected function linkFurtherResults( $results ) {
		return ( $this->mInline && $results->hasFurtherResults() && ( $this->mSearchlabel !== '' ) );
	}

	/**
	 * Return an array describing the parameters of specifically text-based
	 * formats, like 'list' and 'table', for use in their getParameters()
	 * functions
	 * 
	 * @since 1.5.0
	 * 
	 * @return array
	 */
	protected function textDisplayParameters() {
		return array(
			array( 'name' => 'intro', 'type' => 'string', 'description' => wfMsg( 'smw_paramdesc_intro' ) ),
			array( 'name' => 'outro', 'type' => 'string', 'description' => wfMsg( 'smw_paramdesc_outro' ) ),
			array( 'name' => 'default', 'type' => 'string', 'description' => wfMsg( 'smw_paramdesc_default' ) ),
		);
	}

	/**
	 * Return an array describing the parameters of the export formats
	 * like 'rss' and 'csv', for use in their getParameters() functions
	 * 
	 * @since 1.5.0
	 * 
	 * @return array
	 */
	protected function exportFormatParameters() {
		return array(
			array( 'name' => 'limit', 'type' => 'int', 'description' => wfMsg( 'smw_paramdesc_limit' ) ),
			array( 'name' => 'headers', 'type' => 'enumeration', 'description' => wfMsg( 'smw_paramdesc_headers' ), 'values' => array( 'show', 'hide', 'plain' ) ),
			array( 'name' => 'mainlabel', 'type' => 'string', 'description' => wfMsg( 'smw_paramdesc_mainlabel' ) ),
			array( 'name' => 'searchlabel', 'type' => 'string', 'description' => wfMsg( 'smw_paramdesc_searchlabel' ) ),
		);
	}

	/**
	 * A function to describe the allowed parameters of a query using
	 * any specific format - most query printers should override this
	 * function
	 * 
	 * @since 1.5.0
	 * 
	 * @return array
	 */
	public function getParameters() {
		return array(
			array( 'name' => 'limit', 'type' => 'int', 'description' => wfMsg( 'smw_paramdesc_limit' ) ),
			array( 'name' => 'headers', 'type' => 'enumeration', 'description' => wfMsg( 'smw_paramdesc_headers' ), 'values' => array( 'show', 'hide', 'plain' ) ),
			array( 'name' => 'mainlabel', 'type' => 'string', 'description' => wfMsg( 'smw_paramdesc_mainlabel' ) ),
			array( 'name' => 'link', 'type' => 'enumeration', 'description' => wfMsg( 'smw_paramdesc_link' ), 'values' => array( 'all', 'subject', 'none' ) ),
		);
	}

}