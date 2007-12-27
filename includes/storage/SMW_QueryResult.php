<?php
/**
 * This file contains classes that are used for representing query results,
 * basically several containers/iterators for accessing all parts of a query result.
 * These classes might once be replaced by interfaces that are implemented
 * by storage-specific classes if this is useful (e.g. for performance gains by
 * lazy retrieval).
 *
 * @author Markus Krötzsch
 */

/**
 * Objects of this class encapsulate the result of a query in SMW. They
 * provide access to the query result and printed data, and to some
 * relevant query parameters that were used.
 * 
 * While the API does not require this, it is ensured that every result row 
 * returned by this object has the same number of elements (columns).
 */
class SMWQueryResult {
	protected $m_content; // array (table) of arrays (rows) of arrays (fields, SMWResultArray)
	protected $m_printrequests; // array of SMWPrintRequest objects, indexed by their natural hash keys
	protected $m_furtherres; // are there more results than the ones given?
	// The following are not part of the result, but specify the input query (needed for further results link):
	protected $m_query; // the query object, must be set on create and is our fallback for all other data
	protected $m_querystring; // string (inline query) version of query
	protected $m_extraprintouts; // the additional SMWPrintRequest objects specified outside $m_querystring
	  // Note: these may differ from m_printrequests, since they do not involve requests given in the querystring

	/**
	 * Initialise the object with an array of SMWPrintRequest objects, which
	 * define the structure of the result "table" (one for each column).
	 */
	public function SMWQueryResult($printrequests, $query, $furtherres=false) {
		$this->m_content = array();
		$this->m_printrequests = $printrequests;
		$this->m_furtherres = $furtherres;
		$this->m_query = $query;
		$this->m_querystring = $query->getQueryString();
		$this->m_extraprintouts = $query->getExtraPrintouts();
	}

	/**
	 * Checks whether it conforms to the given schema of print requests, adds the
	 * row to the result, and returns true. Otherwise returns false.
	 * TODO: should we just skip the checks and trust our callers?
	 */
	public function addRow($row) {
		reset($row);
		reset($this->m_printrequests);
		$pr = current($this->m_printrequests);
		$ra = current($row);

		while ( $pr !== false ) {
			if (!($ra instanceof SMWResultArray)) {
				return false;
			}
			//compare print-request signatures:
			if ($pr->getHash() !== $ra->getPrintRequest()->getHash()) {
				return false;
			}
			$pr = next($this->m_printrequests);
			$ra = next($row);
		}
		if ($ra !== false) {
			return false;
		}
		$this->m_content[] = $row;
		reset($this->m_content);
		return true;
	}


	/**
	 * Return the next result row as an array of
	 * SMWResultArray objects.
	 */
	public function getNext() {
		$result = current($this->m_content);
		next($this->m_content);
		return $result;
	}

	/**
	 * Return number of available results.
	 */
	public function getCount() {
		return count($this->m_content);
	}

	/**
	 * Return the number of columns of result values that each row 
	 * in this result set contains.
	 */
	public function getColumnCount() {
		return count($this->m_printrequests);
	}

	/**
	 * Return array of print requests (needed for printout since they contain 
	 * property labels).
	 */
	public function getPrintRequests() {
		return $this->m_printrequests;
	}

	/**
	 * Returns the query string, that sets the conditions for the entities to be
	 * returned.
	 */
	public function getQueryString() {
		return $this->m_querystring;
	}

	/**
	 * Would there be more query results that were 
	 * not shown due to a limit?
	 */
	public function hasFurtherResults() {
		return $this->m_furtherres;
	}

	/**
	 * Return error array, possibly empty.
	 */
	public function getErrors() {
		// just use query errors (no own errors generated so up to now)
		return $this->m_query->getErrors();
	}

	/**
	 * Return URL of a page that displays those search results
	 * (and enables browsing results, and is accessible even without
	 * JavaScript enabled browsers).
	 */
	public function getQueryURL() {
		/// TODO implement (requires some way of generating/maintaining this URL as part of the query, and setting it when creating this result)
		$title = Title::makeTitle(NS_SPECIAL, 'ask');
		$params = 'query=' . urlencode($this->m_querystring);
		if ($this->m_query->sortkey != false) {
			$params .= '&sort=' . urlencode($this->m_query->sortkey);
			if ($this->m_query->ascending) {
				$params .= '&order=ASC';
			} else {
				$params .= '&order=DESC';
			}
		}
		return $title->getFullURL($params);
	}
	
	/**
	 * Return titlestring of a page that displays those search results
	 * (and enables browsing results, and is accessible even without
	 * JavaScript enabled browsers).
	 */
	public function getQueryTitle() {
		$title = Title::makeTitle(NS_SPECIAL, 'ask');
		$titlestring = $title->getPrefixedText();
		$params = array($this->m_querystring);
		foreach ($this->m_extraprintouts as $printout) {
			$params[] = $printout->getSerialisation();
		}
		if ($this->m_query->sortkey != false) {
			$params[] = 'sort=' . $this->m_query->sortkey;
			if ($this->m_query->ascending) {
				$params[] = 'order=ASC';
			} else {
				$params[] = 'order=DESC';
			}
		}
		foreach ($params as $p) {
			$p = str_replace(array('/','=','-','%'),array('-2F','-3D','-2D','-'), rawurlencode($p));
			$titlestring .= '/' . $p;
		}
		return $titlestring;
	}
}

/**
 * Container for the contents of a single result field of a query result,
 * i.e. basically an array of Titles or SMWDataValues with some additional
 * parameters.
 */
class SMWResultArray {
	protected $printrequest;
	protected $content;
	protected $furtherres;

	public function SMWResultArray($content, SMWPrintRequest $printrequest, $furtherres = false) {
		$this->content = $content;
		reset($this->content);
		$this->printrequest = $printrequest;
		$this->furtherres = $furtherres;
	}

	/**
	 * Returns an array of objects. Depending on the type of 
	 * results, they are either Titles or SMWDataValues.
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * Return the next result object (Title or SMWDataValue).
	 */
	public function getNextObject() {
		$result = current($this->content);
		next($this->content);
		return $result;
	}

	/**
	 * Return the main text representation of the next result object 
	 * (Title or SMWDataValue) as HTML.
	 *
	 * The parameter $linker controls linking of title values and should
	 * be some Linker object (or NULL for no linking).
	 * @DEPRECATED Use getNextText()
	 */
	public function getNextHTMLText($linker = NULL) {
		$object = current($this->content);
		next($this->content);
		if ($object instanceof SMWDataValue) { //print data values
			if ($object->getTypeID() == '_wpg') { // prefer "long" text for page-values
				return $object->getLongHTMLText($linker);
			} else {
				return $object->getShortHTMLText($linker);
			}
		} else {
			return false;
		}
	}

	/**
	 * Return the main text representation of the next result object 
	 * (Title or SMWDataValue) as Wikitext. The parameter $linked controls 
	 * linking of title values and should be non-NULL and non-false if this 
	 * is desired.
	 * @DEPRECATED Use getNextText()
	 */
	public function getNextWikiText($linked = NULL) {
		$object = current($this->content);
		next($this->content);
		if ($object instanceof SMWDataValue) { //print data values
			if ($object->getTypeID() == '_wpg') { // prefer "long" text for page-values
				return $object->getLongWikiText($linked);
			} else {
				return $object->getShortWikiText($linked);
			}
		} else {
			return false;
		}
	}

	/**
	 * Return the main text representation of the next result object 
	 * (Title or SMWDataValue) in the specified format.
	 *
	 * The parameter $linker controls linking of title values and should
	 * be some Linker object (or NULL for no linking). At some stage its 
	 * interpretation should be part of the generalised SMWDataValue.
	 */
	public function getNextText($outputmode, $linker = NULL) {
		$object = current($this->content);
		next($this->content);
		if ($object instanceof SMWDataValue) { //print data values
			if ($object->getTypeID() == '_wpg') { // prefer "long" text for page-values
				return $object->getLongText($outputmode, $linker);
			} else {
				return $object->getShortText($outputmode, $linker);
			}
		} else {
			return false;
		}
	}


	/**
	 * Would there be more query results that were 
	 * not shown due to a limit?
	 */
	public function hasFurtherResults() {
		return $this->furtherres;
	}

	/**
	 * Return an SMWPrintRequest object describing what is contained in this
	 * result set.
	 */
	public function getPrintRequest() {
		return $this->printrequest;
	}
}

