<?php
/**
 * Abstract base class for printing qurey results.
 * @author Markus Krötzsch
 */

/**
 * Abstract base class for SMW's novel query printing mechanism. It implements
 * part of the former functionality of SMWInlineQuery (everything related to
 * output formatting and the correspoding parameters) and is subclassed by concrete
 * printers that provide the main formatting functionality.
 */
abstract class SMWResultPrinter {

	protected $m_params;
	// parameters relevant for printers in general:
	protected $mFormat;  // a string identifier describing a valid format
	protected $mIntro = ''; // text to print before the output in case it is *not* empty
	protected $mSearchlabel = NULL; // text to use for link to further results, or empty if link should not be shown
	protected $mLinkFirst; // should article names of the first column be linked?
	protected $mLinkOthers; // should article names of other columns (besides the first) be linked?
	protected $mDefault = ''; // default return value for empty queries
	protected $mShowHeaders = true; // should the headers (property names) be printed?
	protected $mShowErrors = true; // should errors possibly be printed?
	protected $mInline; // is this query result "inline" in some page (only then a link to unshown results is created, error handling may also be affected)
	protected $mLinker; // Linker object as needed for making result links. Might come from some skin at some time.
	

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
	 * Main entry point: takes an SMWQueryResult and parameters
	 * given as key-value-pairs in an array, and returns the 
	 * serialised version of the results, formatted as HTML or Wiki
	 * or whatever is specified. Normally this is not overwritten by
	 * subclasses.
	 */
	public function getResult($results, $params, $outputmode) {
		$this->readParameters($params,$outputmode);
		if ($results->getCount() == 0) { // no results, take over processing
			if (!$results->hasFurtherResults()) {
				return htmlspecialchars($this->mDefault) . $this->getErrorString($results);
			} elseif ($this->mInline) {
				$label = $this->mSearchlabel;
				if ($label === NULL) { //apply defaults
					$label = wfMsgForContent('smw_iq_moreresults');
				}
				if ($label != '') {
					$link = $results->getQueryLink($label);
					$result = $link->getText($outputmode,$this->mLinker);
				}
				$result .= $this->getErrorString($results);
				return $result;
			}
		}
		return $this->getResultText($results,$outputmode) . $this->getErrorString($results);
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
			if ($outputmode != SMW_OUTPUT_WIKI) {
				$this->mIntro = htmlspecialchars($this->mIntro);
			}
		}
		if (array_key_exists('searchlabel', $params)) {
			$this->mSearchlabel = $params['searchlabel'];
			if ($outputmode != SMW_OUTPUT_WIKI) {
				$this->mSearchlabel = htmlspecialchars($this->mSearchlabel);
			}
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
			if ($outputmode != SMW_OUTPUT_WIKI) {
				$this->mDefault = htmlspecialchars($this->mDefault);
			}
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
	 * @param $firstrow True of this is the first result row (having special linkage settings).
	 */
	protected function getLinker($firstcol = false) {
		if ( ($firstcol && $this->mLinkFirst) || (!$firstcol && $this->mLinkOthers) ) {
			return $this->mLinker;
		} else {
			return NULL;
		}
	}

	/**
	 * Some printers can produce not only embeddable HTML or Wikitext, but
	 * can also produce stand-alone files. An example is RSS or iCalendar.
	 * This function returns the mimetype string that this file would have,
	 * or FALSE if no standalone files are produced.
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
	 * Change if errors should be shown-
	 */
	public function setShowErrors($show) {
		$this->mShowErrors = $show;
	}

	/**
	 * @DEPRECATED (since >1.0) use getResult()
	 */
	public function getResultHTML($results, $params) {
		return $this->getResult($results,$params,SMW_OUTPUT_HTML);
	}

	/**
	 * Return HTML version of serialised results.
	 * @DEPRECATED: (since >1.0) Legacy method, use getResultText instead
	 */
	protected function getHTML($res) {
		return $this->getResultText($res,SMW_OUTPUT_HTML);
	}

	/**
	 * Generate a link to further results of the given query, using syntactic encoding
	 * as appropriate for $outputmode.
	 * @DEPRECATED (since >1.1) This function no longer does anything interesting. Intelligence moved to SMWInfolink: Use the code as given below directly!
	 */
	protected function getFurtherResultsLink($outputmode, $res, $label) {
		$link = $res->getQueryLink($label);
		return $link->getText($outputmode,$this->mLinker);
	}

}
