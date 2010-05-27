<?php
/**
 * This file contains classes that are used for representing query results,
 * basically several containers/iterators for accessing all parts of a query result.
 * These classes might once be replaced by interfaces that are implemented
 * by storage-specific classes if this is useful (e.g. for performance gains by
 * lazy retrieval).
 * 
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * 
 * @file
 * @ingroup SMWQuery
 */

/**
 * Objects of this class encapsulate the result of a query in SMW. They
 * provide access to the query result and printed data, and to some
 * relevant query parameters that were used.
 *
 * Standard access is provided through the iterator function getNext(),
 * which returns an array ("table row") of SMWResultArray objects ("table cells").
 * It is also possible to access the set of result pages directly using
 * getResults(). This is useful for printers that disregard printouts and
 * only are interested in the actual list of pages.
 * @ingroup SMWQuery
 */
class SMWQueryResult {
	/**
	 * Array of SMWWikiPageValue objects that are the basis for this result
	 * @var Array of SMWWikiPageValue
	 */
	protected $mResults;
	
	/**
	 * Array of SMWPrintRequest objects, indexed by their natural hash keys
	 * @var Array of SMWPrintRequest
	 */ 
	protected $mPrintRequests;
	
	/**
	 * Are there more results than the ones given?
	 * @var boolean
	 */ 
	protected $mFurtherResults;
	
	/**
	 * The query object for which this is a result, must be set on create and is the source of
	 * data needed to create further result links.
	 * @var SMWQuery
	 */
	protected $mQuery;
	
	/**
	 * The SMWStore object used to retrieve further data on demand.
	 * @var SMWStore
	 */
	protected $mStore;

	/**
	 * Initialise the object with an array of SMWPrintRequest objects, which
	 * define the structure of the result "table" (one for each column).
	 * 
	 * TODO: Update documentation
	 * 
	 * @param array of SMWPrintRequest $printRequests
	 * @param SMWQuery $query
	 * @param array of SMWWikiPageValue $results
	 * @param SMWStore $store
	 * @param boolean $furtherRes
	 */
	public function SMWQueryResult( array $printRequests, SMWQuery $query, array $results, SMWStore $store, $furtherRes = false ) {
		$this->mResults = $results;
		reset( $this->mResults );
		$this->mPrintRequests = $printRequests;
		$this->mFurtherResults = $furtherRes;
		$this->mQuery = $query;
		$this->mStore = $store;
	}

	/**
	 * Return the next result row as an array of SMWResultArray objects, and
	 * advance the internal pointer.
	 * 
	 * @return SMWResultArray or false
	 */
	public function getNext() {
		$page = current( $this->mResults );
		next( $this->mResults );
		
		if ( $page === false ) return false;
		
		$row = array();
		
		foreach ( $this->mPrintRequests as $p ) {
			$row[] = new SMWResultArray( $page, $p, $this->mStore );
		}
		
		return $row;
	}

	/**
	 * Return number of available results.
	 *
	 * @return integer
	 */
	public function getCount() {
		return count( $this->mResults );
	}

	/**
	 * Return an array of SMWWikiPageValue objects that make up the
	 * results stored in this object.
	 * 
	 * @return array of SMWWikiPageValue
	 */
	public function getResults() {
		return $this->mResults;
	}

	/**
	 * Return the number of columns of result values that each row
	 * in this result set contains.
	 * 
	 * @return integer
	 */
	public function getColumnCount() {
		return count( $this->mPrintRequests );
	}

	/**
	 * Return array of print requests (needed for printout since they contain
	 * property labels).
	 * 
	 * @return array of SMWPrintRequest
	 */
	public function getPrintRequests() {
		return $this->mPrintRequests;
	}

	/**
	 * Returns the query string defining the conditions for the entities to be
	 * returned.
	 * 
	 * @return string
	 */
	public function getQueryString() {
		return $this->mQuery->getQueryString();
	}

	/**
	 * Would there be more query results that were not shown due to a limit?
	 * 
	 * @return boolean
	 */
	public function hasFurtherResults() {
		return $this->mFurtherResults;
	}

	/**
	 * Return error array, possibly empty.
	 * 
	 * @return array
	 */
	public function getErrors() {
		// Just use query errors, as no errors generated in this class at the moment.
		return $this->mQuery->getErrors();
	}

	/**
	 * Adds an array of erros.
	 * 
	 * @param array $errors
	 */
	public function addErrors( array $errors ) {
		$this->mQuery->addErrors( $errors );
	}

	/**
	 * Create an SMWInfolink object representing a link to further query results.
	 * This link can then be serialised or extended by further params first.
	 * The optional $caption can be used to set the caption of the link (though this
	 * can also be changed afterwards with SMWInfolink::setCaption()). If empty, the
	 * message 'smw_iq_moreresults' is used as a caption.
	 * 
	 * @param mixed $caption A caption string or false
	 * 
	 * @return SMWInfolink
	 */
	public function getQueryLink( $caption = false ) {
		$params = array( trim( $this->mQuery->getQueryString() ) );
		
		foreach ( $this->mQuery->getExtraPrintouts() as $printout ) {
			$params[] = $printout->getSerialisation();
		}
		
		if ( count( $this->mQuery->sortkeys ) > 0 ) {
			$order = implode( ',', $this->mQuery->sortkeys );
			$sort = implode( ',', array_keys( $this->mQuery->sortkeys ) );
			
			if ( $sort != '' || $order != 'ASC' ) {
				$params['order'] = $order;
				$params['sort'] = $sort;			
			}
		}
		
		if ( $caption == false ) {
			smwfLoadExtensionMessages( 'SemanticMediaWiki' );
			$caption = ' ' . wfMsgForContent( 'smw_iq_moreresults' ); // The space is right here, not in the QPs!
		}
		
		// Note: the initial : prevents SMW from reparsing :: in the query string.
		$result = SMWInfolink::newInternalLink( $caption, ':Special:Ask', false, $params );
		
		return $result;
	}

}

/**
 * Container for the contents of a single result field of a query result,
 * i.e. basically an array of SMWDataValues with some additional parameters.
 * The content of the array is fetched on demand only.
 * @ingroup SMWQuery
 */
class SMWResultArray {
	/**
	 * @var SMWPrintRequest
	 */
	protected $mPrintRequest;
	
	/**
	 * @var SMWWikiPageValue
	 */
	protected $mResult;
	
	/**
	 * @var SMWStore
	 */
	protected $mStore;
	
	/**
	 * @var array of SMWDataValue or false 
	 */
	protected $mContent;

	static protected $catCacheObj = false;
	static protected $catCache = false;

	/**
	 * Constructor.
	 * 
	 * @param SMWWikiPageValue $resultPage
	 * @param SMWPrintRequest $printRequest
	 * @param SMWStore $store
	 */
	public function SMWResultArray( SMWWikiPageValue $resultPage, SMWPrintRequest $printRequest, SMWStore $store ) {
		$this->mResult = $resultPage;
		$this->mPrintRequest = $printRequest;
		$this->mStore = $store;
		$this->mContent = false;
	}

	/**
	 * Returns the SMWWikiPageValue object to which this SMWResultArray refers.
	 * If you only care for those objects, consider using SMWQueryResult::getResults()
	 * directly.
	 * 
	 * @return SMWWikiPageValue
	 */
	public function getResultSubject() {
		return $this->mResult;
	}

	/**
	 * Returns an array of SMWDataValue objects that contain the results of
	 * the given print request for the given result object.
	 * 
	 * @return array of SMWDataValue or false
	 */
	public function getContent() {
		$this->loadContent();
		return $this->mContent;
	}

	/**
	 * Return an SMWPrintRequest object describing what is contained in this
	 * result set.
	 * 
	 * @return SMWPrintRequest
	 */
	public function getPrintRequest() {
		return $this->mPrintRequest;
	}

	/**
	 * Return the next SMWDataValue object or false if no further object exists.
	 * 
	 * @return SMWDataValue
	 */
	public function getNextObject() {
		$this->loadContent();
		
		$result = current( $this->mContent );
		next( $this->mContent );
		
		return $result;
	}

	/**
	 * Return the main text representation of the next SMWDataValue object
	 * in the specified format, or false if no further object exists.
	 *
	 * The parameter $linker controls linking of title values and should
	 * be some Linker object (or NULL for no linking). At some stage its
	 * interpretation should be part of the generalised SMWDataValue.
	 * 
	 * @param $outputMode
	 * @param $linker
	 */
	public function getNextText( $outputMode, $linker = null ) {
		$object = $this->getNextObject();
		
		if ( $object instanceof SMWDataValue ) { // Print data values.
			return ( ( $object->getTypeID() == '_wpg' ) || ( $object->getTypeID() == '__sin' ) ) ?  // Prefer "long" text for page-values.
		       $object->getLongText( $outputMode, $linker ) :
			   $object->getShortText( $outputMode, $linker );
		} else {
			return false;
		}
	}

	/**
	 * Load results of the given print request and result subject. This is only
	 * done when needed.
	 */
	protected function loadContent() {
		if ( $this->mContent !== false ) return;
		
		wfProfileIn( 'SMWQueryResult::loadContent (SMW)' );
		
		switch ( $this->mPrintRequest->getMode() ) {
			case SMWPrintRequest::PRINT_THIS: // NOTE: The limit is ignored here.
				if ( $this->mPrintRequest->getOutputFormat() ) {
					$res = clone $this->mResult;
					$res->setOutputFormat( $this->mPrintRequest->getOutputFormat() );
				} else {
					$res = $this->mResult;
				}
				
				$this->mContent = array( $res );
			break;
			case SMWPrintRequest::PRINT_CATS:
				// Always recompute cache here to ensure output format is respected.
				self::$catCache = $this->mStore->getPropertyValues( $this->mResult, SMWPropertyValue::makeProperty( '_INST' ), $this->getRequestOptions( false ), $this->mPrintRequest->getOutputFormat() );
				self::$catCacheObj = $this->mResult->getHash();
				
				$limit = $this->mPrintRequest->getParameter( 'limit' );
				$this->mContent = ( $limit === false ) ? ( self::$catCache ) : array_slice( self::$catCache, 0, $limit );
			break;
			case SMWPrintRequest::PRINT_PROP:
				$this->mContent = $this->mStore->getPropertyValues( $this->mResult, $this->mPrintRequest->getData(), $this->getRequestOptions(), $this->mPrintRequest->getOutputFormat() );
				
				// Print one component of a multi-valued string.
				// Known limitation: the printrequest still is of type _rec, so if printers check
				// for this then they will not recognize that it returns some more concrete type.
				if ( ( $this->mPrintRequest->getTypeID() == '_rec' ) && ( $this->mPrintRequest->getParameter( 'index' ) !== false ) ) {
					$pos = $this->mPrintRequest->getParameter( 'index' ) - 1;
					$newcontent = array();
					
					foreach ( $this->mContent as $listdv ) {
						$dvs = $listdv->getDVs();
						if ( ( array_key_exists( $pos, $dvs ) ) && ( $dvs[$pos] !== null ) ) {
							$newcontent[] = $dvs[$pos];
						}
					}
					
					$this->mContent = $newcontent;
				}
			break;
			case SMWPrintRequest::PRINT_CCAT: ///NOTE: The limit is ignored here.
				if ( self::$catCacheObj != $this->mResult->getHash() ) {
					self::$catCache = $this->mStore->getPropertyValues( $this->mResult, SMWPropertyValue::makeProperty( '_INST' ) );
					self::$catCacheObj = $this->mResult->getHash();
				}
				
				$found = '0';
				$prkey = $this->mPrintRequest->getData()->getDBkey();
				
				foreach ( self::$catCache as $cat ) {
					if ( $cat->getDBkey() == $prkey ) {
						$found = '1';
						break;
					}
				}
				
				$dv = SMWDataValueFactory::newTypeIDValue( '_boo' );
				$dv->setOutputFormat( $this->mPrintRequest->getOutputFormat() );
				$dv->setDBkeys( array( $found ) );
				$this->mContent = array( $dv );
			break;
			default: $this->mContent = array(); // Unknown print request.
		}
		
		reset( $this->mContent );
		
		wfProfileOut( 'SMWQueryResult::loadContent (SMW)' );
	}

	/**
	 * Make a request option object based on the given parameters, and
	 * return NULL if no such object is required. The parameter defines
	 * if the limit should be taken into account, which is not always desired
	 * (especially if results are to be cached for future use).
	 * 
	 * @param boolean $useLimit
	 * 
	 * @return SMWRequestOptions or null
	 */
	protected function getRequestOptions( $useLimit = true ) {
		$limit = $useLimit ? $this->mPrintRequest->getParameter( 'limit' ) : false;
		$order = trim( $this->mPrintRequest->getParameter( 'order' ) );
		
		// Important: use "!=" for order, since trim() above does never return "false", use "!==" for limit since "0" is meaningful here.
		if ( ( $limit !== false ) || ( $order != false ) ) { 
			$options = new SMWRequestOptions();
			
			if ( $limit !== false ) $options->limit = trim( $limit );
			
			if ( ( $order == 'descending' ) || ( $order == 'reverse' ) || ( $order == 'desc' ) ) {
				$options->sort = true;
				$options->ascending = false;
			} elseif ( ( $order == 'ascending' ) || ( $order == 'asc' ) ) {
				$options->sort = true;
				$options->ascending = true;
			}
		} else {
			$options = null;
		}
		
		return $options;
	}

}