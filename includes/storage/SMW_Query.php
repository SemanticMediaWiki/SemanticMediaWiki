<?php
/**
 * This file contains the class for representing queries in SMW, each
 * consisting of a query description and possible query parameters.
 *
 * @author Markus Krötzsch
 */

global $smwgIP;
require_once($smwgIP . '/includes/storage/SMW_Description.php');

/**
 * Representation of queries in SMW, each consisting of a query 
 * description and various parameters. Some settings might also lead to 
 * changes in the query description.
 *
 * Most additional query parameters (limit, sort, ascending, ...) are 
 * interpreted as in SMWRequestOptions (though the latter contains some
 * additional settings).
 */
class SMWQuery {

	const MODE_INSTANCES = 1; // normal instance retrieval
	const MODE_COUNT = 2; // find result count only
	const MODE_DEBUG = 3; // prepare query, but show debug data instead of executing it
	const MODE_NONE = 4;  // do nothing with the query

	public $sort = false;
	public $ascending = true;
	public $sortkey = false;
	public $querymode = SMWQuery::MODE_INSTANCES;

	protected $m_limit;
	protected $m_offset = 0;
	protected $m_description;
	protected $m_errors = array(); // keep any errors that occurred so far
	protected $m_querystring = false; // string (inline query) version (if fixed and known)
	protected $m_inline; // query used inline? (required for finding right default parameters)
	protected $m_extraprintouts = array(); // SMWPrintoutRequest objects supplied outside querystring

	public function SMWQuery($description = NULL, $inline = false) {
		global $smwgQMaxLimit, $smwgQMaxInlineLimit;
		if ($inline) {
			$this->m_limit = $smwgQMaxInlineLimit;
		} else {
			$this->m_limit = $smwgQMaxLimit;
		}
		$this->m_inline = $inline;
		$this->m_description = $description;
		$this->applyRestrictions();
	}

	public function setDescription(SMWDescription $description) {
		$this->m_description = $description;
		foreach ($extraprintouts as $printout) {
			$this->m_description->addPrintRequest($printout);
		}
		$this->applyRestrictions();
	}

	public function getDescription() {
		return $this->m_description;
	}

	public function setExtraPrintouts($extraprintouts) {
		$this->m_extraprintouts = $extraprintouts;
		if ($this->m_description !== NULL) {
			foreach ($extraprintouts as $printout) {
				$this->m_description->addPrintRequest($printout);
			}
		}
	}

	public function getExtraPrintouts() {
		return $this->m_extraprintouts;
	}

	public function getErrors() {
		return $this->m_errors;
	}

	public function addErrors($errors) {
		$this->m_errors = array_merge($this->m_errors, $errors);
	}

	public function setQueryString($querystring) {
		$this->m_querystring = $querystring;
	}

	public function getQueryString() {
		if ($this->m_querystring !== false) {
			return $this->m_querystring;
		} elseif ($this->m_description !== NULL) {
			return $this->m_description->getQueryString();
		} else {
			return '';
		}
	}

	public function getOffset() {
		return $this->m_offset;
	}

	/**
	 * Set an offset for the returned query results. The current limit is taken into
	 * account such that the offset cannot be so large that no results are can ever 
	 * be returned at all.
	 * The function returns the chosen offset.
	 */
	public function setOffset($offset) {
		$this->m_offset = min($this->m_limit - 1, $offset); //select integer between 0 and current limit -1;
		return $this->m_offset;
	}

	public function getLimit() {
		return $this->m_limit;
	}

	/**
	 * Set a limit for number of query results. The set limit might be restricted by the
	 * current offset so as to ensure that the number of the last considered result does not
	 * exceed the maximum amount of supported results.
	 * The function returns the chosen limit.
	 * NOTE: it makes sense to have limit==0 e.g. to only show a link to the search special
	 */
	public function setLimit($limit, $restrictinline = true) {
		global $smwgQMaxLimit, $smwgQMaxInlineLimit;
		if ($this->m_inline && $restrictinline) {
			$maxlimit = $smwgQMaxInlineLimit;
		} else {
			$maxlimit = $smwgQMaxLimit;
		}
		$this->m_limit = min($maxlimit - $this->m_offset, $limit);
		return $this->m_limit;
	}

	/**
	 * Apply structural restrictions to the current description.
	 */
	public function applyRestrictions() {
		global $smwgQMaxSize, $smwgQMaxDepth;
		if ($this->m_description !== NULL) {
			$maxsize = $smwgQMaxSize;
			$maxdepth = $smwgQMaxDepth;
			$log = array();
			$this->m_description = $this->m_description->prune($maxsize, $maxdepth, $log);
			if (count($log) > 0) {
				$this->m_errors[] = wfMsgForContent('smw_querytoolarge',implode(', ' , $log));
			}
		}
	}


}

