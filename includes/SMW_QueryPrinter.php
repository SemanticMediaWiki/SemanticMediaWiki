<?php
/**
 * File with abstract base class for printing query results.
 * @author Markus Krötzsch
 * @file
 * @ingroup SMWQuery
 */

/**
 * Abstract base class for SMW's novel query printing mechanism. It implements
 * part of the former functionality of SMWInlineQuery (everything related to
 * output formatting and the correspoding parameters) and is subclassed by concrete
 * printers that provide the main formatting functionality.
 */
abstract class SMWResultPrinter {

	protected $m_params;

	/** Text to print before the output in case it is *not* empty; assumed to be wikitext.
	  * Normally this is handled in SMWResultPrinter and can be ignored by subclasses. */
	protected $mIntro = '';
	
	/** Text to use for link to further results, or empty if link should not be shown.
	 *  Unescaped! Use SMWResultPrinter::getSearchLabel() and SMWResultPrinter::linkFurtherResults()
	 *  instead of accessing this directly. */
	protected $mSearchlabel = NULL;

	/** Default return value for empty queries. Unescaped. Normally not used in sub-classes! */
	protected $mDefault = '';

	// parameters relevant for printers in general:
	protected $mFormat;  // a string identifier describing a valid format
	protected $mLinkFirst; // should article names of the first column be linked?
	protected $mLinkOthers; // should article names of other columns (besides the first) be linked?
	protected $mShowHeaders = true; // should the headers (property names) be printed?
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

	private static $mRecursionDepth = 0; // increment while expanding templates inserted during printout; stop expansion at some point

	/**
	 * Constructor. The parameter $format is a format string
	 * that may influence the processing details.
	 */
	public function SMWResultPrinter($format, $inline) {
		global $smwgQDefaultLinking;
		$this->mFormat = $format;
		$this->mInline = $inline;
		$this->mLinkFirst = ($smwgQDefaultLinking != 'none');
		$this->mLinkOthers = ($smwgQDefaultLinking == 'all');
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
	 * effectively can get down to level 3.
	 */
	public function getResult($results, $params, $outputmode) {
		global $wgParser;
		$this->isHTML = false;
		$this->hasTemplates = false;
		$this->readParameters($params,$outputmode);

		// Default output for normal printers:
		if ( ($outputmode != SMW_OUTPUT_FILE) && // not in FILE context,
		     ($results->getCount() == 0) && // no results,
		     ($this->getMimeType($results) === false)) { // normal printer -> take over processing
			if (!$results->hasFurtherResults()) {
				return $this->escapeText($this->mDefault,$outputmode) . $this->getErrorString($results);
			} elseif ($this->mInline) {
				$label = $this->mSearchlabel;
				if ($label === NULL) { //apply defaults
					wfLoadExtensionMessages('SemanticMediaWiki');
					$label = wfMsgForContent('smw_iq_moreresults');
				}
				if ($label != '') {
					$link = $results->getQueryLink($this->escapeText($label,$outputmode));
					$result = $link->getText($outputmode,$this->mLinker);
				} else {
					$result = '';
				}
				$result .= $this->getErrorString($results);
				return $result;
			}
		}

		// Get output from printer:
		$result = $this->getResultText($results,$outputmode);
		if ($outputmode == SMW_OUTPUT_FILE) { // just return result in file mode
			return $result;
		}
		$result .= $this->getErrorString($results); // append errors
		if ( (!$this->isHTML) && ($this->hasTemplates) ) { // preprocess embedded templates if needed
			if ( ($wgParser->getTitle() instanceof Title) && ($wgParser->getOptions() instanceof ParserOptions) ) {
				SMWResultPrinter::$mRecursionDepth++;
				if (SMWResultPrinter::$mRecursionDepth <= 2) { // restrict recursion
					$result = '[[SMW::off]]' . $wgParser->replaceVariables($result) . '[[SMW::on]]';
				} else {
					$result = ''; /// TODO: explain problem (too much recursive parses)
				}
				SMWResultPrinter::$mRecursionDepth--;
			} else { // not during parsing, no preprocessing needed, still protect the result
				$result = '[[SMW::off]]' . $result . '[[SMW::on]]';
			}
		}

		if ( ($this->isHTML) && ($outputmode == SMW_OUTPUT_WIKI) ) {
			$result = array($result, 'isHTML' => true);
		} elseif ( (!$this->isHTML) && ($outputmode == SMW_OUTPUT_HTML) ) {
			SMWResultPrinter::$mRecursionDepth++;
			// check whether we are in an existing parse, or if we should start a new parse for $wgTitle
			if (SMWResultPrinter::$mRecursionDepth <= 2) { // retrict recursion
				if ( ($wgParser->getTitle() instanceof Title) && ($wgParser->getOptions() instanceof ParserOptions) ) {
					$result = $wgParser->recursiveTagParse($result);
				} else {
					global $wgTitle;
					$popt = new ParserOptions();
					$popt->setEditSection(false);
					$pout = $wgParser->parse($result . '__NOTOC__', $wgTitle, $popt);
					/// NOTE: as of MW 1.14SVN, there is apparently no better way to hide the TOC
					$result = $pout->getText();
				}
			} else {
				$result = ''; /// TODO: explain problem (too much recursive parses)
			}
			SMWResultPrinter::$mRecursionDepth--;
		}

		if ( ($this->mIntro) && ($results->getCount() > 0) ) {
			if ($outputmode == SMW_OUTPUT_HTML) {
				global $wgParser;
				$result = $wgParser->recursiveTagParse($this->mIntro) . $result;
			} else {
				$result = $this->mIntro . $result;
			}
		}
		return $result;
	}

	/**
	 * Read an array of parameter values given as key-value-pairs and
	 * initialise internal member fields accordingly. Possibly overwritten
	 * (extended) by subclasses.
	 */
	protected function readParameters($params,$outputmode) {
		$this->m_params = $params;
		if (array_key_exists('intro', $params)) {
			$this->mIntro = str_replace('_',' ',$params['intro']);
		}
		if (array_key_exists('searchlabel', $params)) {
			$this->mSearchlabel = $params['searchlabel'];
		}
		if (array_key_exists('link', $params)) {
			switch (strtolower($params['link'])) {
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
		if (array_key_exists('default', $params)) {
			$this->mDefault = str_replace('_',' ',$params['default']);
		}
		if (array_key_exists('headers', $params)) {
			if ( 'hide' == strtolower(trim($params['headers']))) {
				$this->mShowHeaders = false;
			} else {
				$this->mShowHeaders = true;
			}
		}
	}

	/**
	 * Return serialised results in specified format.
	 * Implemented by subclasses.
	 */
	abstract protected function getResultText($res, $outputmode);

	/**
	 * Depending on current linking settings, returns a linker object
	 * for making hyperlinks or NULL if no links should be created.
	 *
	 * @param $firstcol True of this is the first result column (having special linkage settings).
	 */
	protected function getLinker($firstcol = false) {
		if ( ($firstcol && $this->mLinkFirst) || (!$firstcol && $this->mLinkOthers) ) {
			return $this->mLinker;
		} else {
			return NULL;
		}
	}

	/**
	 * Some printers do not mainly produce embeddable HTML or Wikitext, but
	 * produce stand-alone files. An example is RSS or iCalendar. This function 
	 * returns the mimetype string that this file would have, or FALSE if no 
	 * standalone files are produced.
	 *
	 * If this function returns something other than FALSE, then the printer will
	 * not be regarded as a printer that displays in-line results. In in-line mode,
	 * queries to that printer will not be executed, but behave as if the user
	 * would have set limit=-1. This saves effort for printers that do not show
	 * results in-line anyway, even if they would be part of the result.
	 */
	public function getMimeType($res) {
		return false;
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
	public function getFileName($res) {
		return false;
	}

	/**
	 * Provides a simple formatted string of all the error messages that occurred.
	 * Can be used if not specific error formatting is desired. Compatible with HTML
	 * and Wiki.
	 */
	public function getErrorString($res) {
		return $this->mShowErrors?smwfEncodeMessages($res->getErrors()):'';
	}

	/**
	 * Set whether errors should be shown. By default they are.
	 */
	public function setShowErrors($show) {
		$this->mShowErrors = $show;
	}

	/**
	 * If $outputmode is SMW_OUTPUT_HTML, escape special characters occuring in the
	 * given text. Otherwise return text as is.
	 */
	protected function escapeText($text, $outputmode) {
		return ($outputmode == SMW_OUTPUT_HTML)?htmlspecialchars($text):$text;
	}

	/**
	 * Get the string the user specified as a text for the "further results" link,
	 * properly escaped for the current output mode.
	 */
	protected function getSearchLabel($outputmode) {
		return $this->escapeText($this->mSearchlabel, $outputmode);
	}

	/**
	 * Check whether a "further results" link would normally be generated for this
	 * result set with the given parameters. Individual result printers may decide to
	 * create or hide such a link independent of that, but this is the default.
	 */
	protected function linkFurtherResults($results) {
		return ($this->mInline && $results->hasFurtherResults() && ($this->mSearchlabel !== ''));
	}


	/**
	 * @deprecated Use SMWResultPrinter::getResult() in SMW >1.0.  This method will last be available in SMW 1.3 and vanish thereafter.
	 */
	public function getResultHTML($results, $params) {
		return $this->getResult($results,$params,SMW_OUTPUT_HTML);
	}

	/**
	 * Return HTML version of serialised results.
	 * @deprecated Use SMWResultPrinter::getResultText() since SMW >1.0.  This method will last be available in SMW 1.3 and vanish thereafter.
	 */
	protected function getHTML($res) {
		return $this->getResultText($res,SMW_OUTPUT_HTML);
	}

	/**
	 * Generate a link to further results of the given query, using syntactic encoding
	 * as appropriate for $outputmode.
	 * @deprecated Since SMW>1.1 this function no longer does anything interesting. Intelligence moved to SMWInfolink. Directly use the code of this method instead of calling it!  This method will last be available in SMW 1.3 and vanish thereafter.
	 */
	protected function getFurtherResultsLink($outputmode, $res, $label) {
		$link = $res->getQueryLink($label);
		return $link->getText($outputmode,$this->mLinker);
	}

}
