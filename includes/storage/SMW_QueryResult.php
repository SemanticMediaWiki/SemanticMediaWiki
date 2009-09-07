<?php
/**
 * This file contains classes that are used for representing query results,
 * basically several containers/iterators for accessing all parts of a query result.
 * These classes might once be replaced by interfaces that are implemented
 * by storage-specific classes if this is useful (e.g. for performance gains by
 * lazy retrieval).
 * @file
 * @ingroup SMWQuery
 * @author Markus Krötzsch
 */

/**
 * Objects of this class encapsulate the result of a query in SMW. They
 * provide access to the query result and printed data, and to some
 * relevant query parameters that were used.
 *
 * While the API does not require this, it is ensured that every result row
 * returned by this object has the same number of elements (columns).
 * @ingroup SMWQuery
 */
class SMWQueryResult {
	/// Array of SMWWikiPageValue objects that are the basis for this result
	protected $m_results;
	/// Array of SMWPrintRequest objects, indexed by their natural hash keys
	protected $m_printrequests;
	/// Are there more results than the ones given?
	protected $m_furtherres;
	/// The query object for which this is a result, must be set on create and is the source of
	/// data needed to create further result links.
	protected $m_query;
	/// The SMWStore object used to retrieve further data on demand.
	protected $m_store;

	/**
	 * Initialise the object with an array of SMWPrintRequest objects, which
	 * define the structure of the result "table" (one for each column).
	 * @todo Update documentation
	 */
	public function SMWQueryResult($printrequests, $query, $results, $store, $furtherres=false) {
		$this->m_results = $results;
		reset($this->m_results);
		$this->m_printrequests = $printrequests;
		$this->m_furtherres = $furtherres;
		$this->m_query = $query;
		$this->m_store = $store;
	}

	/**
	 * Return the next result row as an array of SMWResultArray objects.
	 */
	public function getNext() {
		$page = current($this->m_results);
		next($this->m_results);
		if ($page === false) return false;
		$row = array();
		foreach ($this->m_printrequests as $p) {
			$row[] = new SMWResultArray($page, $p, $this->m_store);
		}
		return $row;
	}

	/**
	 * Return number of available results.
	 */
	public function getCount() {
		return count($this->m_results);
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
	 * Returns the query string defining the conditions for the entities to be
	 * returned.
	 */
	public function getQueryString() {
		return $this->m_query->getQueryString();
	}

	/**
	 * Would there be more query results that were not shown due to a limit?
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

	public function addErrors($errors) {
		$this->m_query->addErrors($errors);
	}

	/**
	 * Create an SMWInfolink object representing a link to further query results.
	 * This link can then be serialised or extended by further params first.
	 * The optional $caption can be used to set the caption of the link (though this
	 * can also be changed afterwards with SMWInfolink::setCaption()). If empty, the
	 * message 'smw_iq_moreresults' is used as a caption.
	 */
	public function getQueryLink($caption = false) {
		$params = array(trim($this->m_query->getQueryString()));
		foreach ($this->m_query->getExtraPrintouts() as $printout) {
			$params[] = $printout->getSerialisation();
		}
		if ( count($this->m_query->sortkeys)>0 ) {
			$psort  = '';
			$porder = '';
			$first = true;
			foreach ( $this->m_query->sortkeys as $sortkey => $order ) {
				if ( $first ) {
					$first = false;
				} else {
					$psort  .= ',';
					$porder .= ',';
				}
				$psort .= $sortkey;
				$porder .= $order;
			}
			if (($psort != '')||($porder != 'ASC')) { // do not mention default sort (main column, ascending)
				$params['sort'] = $psort;
				$params['order'] = $porder;
			}
		}
		if ($caption == false) {
			wfLoadExtensionMessages('SemanticMediaWiki');
			$caption = ' ' . wfMsgForContent('smw_iq_moreresults'); // the space is right here, not in the QPs!
		}
		$result = SMWInfolink::newInternalLink($caption,':Special:Ask', false, $params);
		// Note: the initial : prevents SMW from reparsing :: in the query string
		return $result;
	}

}

/**
 * Container for the contents of a single result field of a query result,
 * i.e. basically an array of SMWDataValues with some additional parameters.
 * @ingroup SMWQuery
 */
class SMWResultArray {
	protected $m_printrequest;
	protected $m_result;
	protected $m_store;
	protected $m_content;

	static protected $catcacheobj = false;
	static protected $catcache = false;

	public function SMWResultArray(SMWWikiPageValue $resultpage, SMWPrintRequest $printrequest, SMWStore $store) {
		$this->m_result = $resultpage;
		$this->m_printrequest = $printrequest;
		$this->m_store = $store;
		$this->m_content = false;
	}

	/**
	 * Returns the SMWWikiPageValue object to which this SMWResultArray refers.
	 */
	public function getResultSubject() {
		return $this->m_result;
	}

	/**
	 * Returns an array of SMWDataValue objects that contain the results of
	 * the given print request for the given result object.
	 */
	public function getContent() {
		$this->loadContent();
		return $this->m_content;
	}

	/**
	 * Return an SMWPrintRequest object describing what is contained in this
	 * result set.
	 */
	public function getPrintRequest() {
		return $this->m_printrequest;
	}

	/**
	 * Return the next SMWDataValue object or false if no further object exists.
	 */
	public function getNextObject() {
		$this->loadContent();
		$result = current($this->m_content);
		next($this->m_content);
		return $result;
	}

	/**
	 * Return the main text representation of the next SMWDataValue object
	 * in the specified format, or false if no further object exists.
	 *
	 * The parameter $linker controls linking of title values and should
	 * be some Linker object (or NULL for no linking). At some stage its
	 * interpretation should be part of the generalised SMWDataValue.
	 */
	public function getNextText($outputmode, $linker = NULL) {
		$object = $this->getNextObject();
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
	 * Load results of the given print request and result subject. This is only
	 * done when needed.
	 */
	protected function loadContent() {
		if ($this->m_content !== false) return;
		wfProfileIn('SMWQueryResult::loadContent (SMW)');
		switch ($this->m_printrequest->getMode()) {
			case SMWPrintRequest::PRINT_THIS:
				if ($this->m_printrequest->getOutputFormat()) {
					$res = clone $this->m_result;
					$res->setOutputFormat($this->m_printrequest->getOutputFormat());
				} else {
					$res = $this->m_result;
				}
				$this->m_content = array($res);
			break;
			case SMWPrintRequest::PRINT_CATS:
				if ( SMWResultArray::$catcacheobj != $this->m_result->getHash() ) {
					SMWResultArray::$catcache = $this->m_store->getPropertyValues($this->m_result,SMWPropertyValue::makeProperty('_INST'));
					SMWResultArray::$catcacheobj = $this->m_result->getHash();
				}
				$this->m_content = SMWResultArray::$catcache;
			case SMWPrintRequest::PRINT_PROP:
				$this->m_content = $this->m_store->getPropertyValues($this->m_result,$this->m_printrequest->getData(), NULL, $this->m_printrequest->getOutputFormat());
			break;
			case SMWPrintRequest::PRINT_CCAT:
				if ( SMWResultArray::$catcacheobj != $this->m_result->getHash() ) {
					SMWResultArray::$catcache = $this->m_store->getPropertyValues($this->m_result,SMWPropertyValue::makeProperty('_INST'));
					SMWResultArray::$catcacheobj = $this->m_result->getHash();
				}
				$found = '0';
				$prkey = $this->m_printrequest->getData()->getDBkey();
				foreach (SMWResultArray::$catcache as $cat) {
					if ($cat->getDBkey() == $prkey) {
						$found = '1';
						break;
					}
				}
				$dv = SMWDataValueFactory::newTypeIDValue('_boo');
				$dv->setOutputFormat($this->m_printrequest->getOutputFormat());
				$dv->setDBkeys(array($found));
				$this->m_content = array($dv);
			break;
			default: $this->m_content = array(); // unknown print request
		}
		reset($this->m_content);
		wfProfileOut('SMWQueryResult::loadContent (SMW)');
	}

}

