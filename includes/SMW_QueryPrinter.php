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

	// parameters:
	protected $mFormat;  // a string identifier describing a valid format
	protected $mIntro = ''; // text to print before the output in case it is *not* empty
	protected $mSearchlabel = NULL; // text to use for link to further results, or empty if link should not be shown
	protected $mLinkFirst; // should article names of the first column be linked?
	protected $mLinkOthers; // should article names of other columns (besides the first) be linked?
	protected $mDefault = ''; // default return value for empty queries
	protected $mShowHeaders = true; // should the headers (property names) be printed?
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
	 * given as key-value-pairs in an array and returns the 
	 * serialised version of the results, formatted as inline HTML
	 * or (for special printers) as RDF or XML or whatever. Normally
	 * not overwritten by subclasses.
	 * @DEPRECATED use getResult()
	 */
	public function getResultHTML($results, $params) {
		return $this->getResult($results,$params,SMW_OUTPUT_HTML);
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
		if ($results->getCount() == 0) {
			if (!$results->hasFurtherResults()) {
				return htmlspecialchars($this->mDefault) . $this->getErrorString($results);
			} elseif ($this->mInline) {
				$label = $this->mSearchlabel;
				if ($label === NULL) { //apply defaults
					$result = '<a href="' . $results->getQueryURL() . '">' . wfMsgForContent('smw_iq_moreresults') . '</a>';
				} else {
					$result = '<a href="' . $results->getQueryURL() . '">' . $label . '</a>';
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
		if (array_key_exists('intro', $params)) {
			$this->mIntro = str_replace('_',' ',$params['intro']);
			if ($outputmode==SMW_OUTPUT_HTML) {
				$this->mIntro = htmlspecialchars($this->mIntro);
			}
		}
		if (array_key_exists('searchlabel', $params)) {
			$this->mSearchlabel = $params['searchlabel'];
			if ($outputmode==SMW_OUTPUT_HTML) {
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
			if ($outputmode==SMW_OUTPUT_HTML) {
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
	 * Return HTML version of serialised results.
	 * @DEPRECATED: Legacy method, use getResultText instead
	 */
	protected function getHTML($res) {
		return $this->getResultText($res,SMW_OUTPUT_HTML);
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
	 * Provides a simple formatted string of all the error messages that occurred.
	 * Can be used if not specific error formatting is desired. Compatible with HTML
	 * and Wiki.
	 */
	protected function getErrorString($res) {
		return smwfEncodeMessages($res->getErrors());
	}

}
