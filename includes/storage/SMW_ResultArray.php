<?php

/**
 * Container for the contents of a single result field of a query result,
 * i.e. basically an array of SMWDataItems with some additional parameters.
 * The content of the array is fetched on demand only.
 * 
 * @ingroup SMWQuery
 * 
 * @author Markus Krötzsch
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SMWResultArray {
	
	/**
	 * @var SMWPrintRequest
	 */
	protected $mPrintRequest;
	
	/**
	 * @var SMWDIWikiPage
	 */
	protected $mResult;
	
	/**
	 * @var SMWStore
	 */
	protected $mStore;
	
	/**
	 * @var array of SMWDataItem or false 
	 */
	protected $mContent;

	static protected $catCacheObj = false;
	static protected $catCache = false;

	/**
	 * Constructor.
	 * 
	 * @param SMWDIWikiPage $resultPage
	 * @param SMWPrintRequest $printRequest
	 * @param SMWStore $store
	 */
	public function __construct( SMWDIWikiPage $resultPage, SMWPrintRequest $printRequest, SMWStore $store ) {
		$this->mResult = $resultPage;
		$this->mPrintRequest = $printRequest;
		$this->mStore = $store;
		$this->mContent = false;
	}

	/**
	 * Get the SMWStore object that this result is based on.
	 *
	 * @return SMWStore
	 */
	public function getStore() {
		return $this->mStore;
	}

	/**
	 * Returns the SMWDIWikiPage object to which this SMWResultArray refers.
	 * If you only care for those objects, consider using SMWQueryResult::getResults()
	 * directly.
	 * 
	 * @return SMWDIWikiPage
	 */
	public function getResultSubject() {
		return $this->mResult;
	}

	/**
	 * Returns an array of SMWDataItem objects that contain the results of
	 * the given print request for the given result object.
	 * 
	 * @return array of SMWDataItem or false
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
	 * Compatibility alias for getNextDatItem().
	 * @deprecated since 1.6. Call getNextDataValue() or getNextDataItem() directly as needed. Method will vanish before SMW 1.7.
	 */
	public function getNextObject() {
		return $this->getNextDataValue();
	}

	/**
	 * Return the next SMWDataItem object or false if no further object exists.
	 * 
	 * @since 1.6
	 * 
	 * @return SMWDataItem or false
	 */
	public function getNextDataItem() {
		$this->loadContent();
		$result = current( $this->mContent );
		next( $this->mContent );
		return $result;
	}

	/**
	 * Set the internal pointer of the array of SMWDataItem objects to its first
	 * element. Return the first SMWDataItem object or false if the array is
	 * empty.
	 * 
	 * @since 1.7.1
	 * 
	 * @return SMWDataItem or false
	 */
	public function reset() {
		$this->loadContent();
		return reset( $this->mContent );
	}

	/**
	 * Return an SMWDataValue object for the next SMWDataItem object or
	 * false if no further object exists.
	 * 
	 * @since 1.6
	 * 
	 * @return SMWDataValue or false
	 */
	public function getNextDataValue() {
		$di = $this->getNextDataItem();
		if ( $di === false ) {
			return false;
		}
		if ( $this->mPrintRequest->getMode() == SMWPrintRequest::PRINT_PROP &&
		     $this->mPrintRequest->getTypeID() == '_rec' &&
		     $this->mPrintRequest->getParameter( 'index' ) !== false ) {
			// Not efficient, but correct: we need to find the right property for
			// the selected index of the record here.
			$pos = $this->mPrintRequest->getParameter( 'index' ) - 1;
			$recordValue = \SMW\DataValueFactory::getInstance()->newDataItemValue( $di,
				$this->mPrintRequest->getData()->getDataItem() );
			$diProperties = $recordValue->getPropertyDataItems();

			if ( array_key_exists( $pos, $diProperties ) &&
				!is_null( $diProperties[$pos] ) ) {
				$diProperty = $diProperties[$pos];
			} else {
				$diProperty = null;
			}
		} elseif ( $this->mPrintRequest->getMode() == SMWPrintRequest::PRINT_PROP ) {
			$diProperty = $this->mPrintRequest->getData()->getDataItem();
		} else {
			$diProperty = null;
		}
		$dv = \SMW\DataValueFactory::getInstance()->newDataItemValue( $di, $diProperty );
		if ( $this->mPrintRequest->getOutputFormat() ) {
			$dv->setOutputFormat( $this->mPrintRequest->getOutputFormat() );
		}

		if ( $dv->canUse() ) {
			return $dv;
		}

		return false;
	}

	/**
	 * Return the main text representation of the next SMWDataItem object
	 * in the specified format, or false if no further object exists.
	 *
	 * The parameter $linker controls linking of title values and should
	 * be some Linker object (or NULL for no linking).
	 *
	 * @param integer $outputMode
	 * @param mixed $linker
	 *
	 * @return string or false
	 */
	public function getNextText( $outputMode, $linker = null ) {
		$dataValue = $this->getNextDataValue();
		if ( $dataValue !== false ) { // Print data values.
			return $dataValue->getShortText( $outputMode, $linker );
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
		

		switch ( $this->mPrintRequest->getMode() ) {
			case SMWPrintRequest::PRINT_THIS: // NOTE: The limit is ignored here.
				$this->mContent = array( $this->mResult );
			break;
			case SMWPrintRequest::PRINT_CATS:
				// Always recompute cache here to ensure output format is respected.
				self::$catCache = $this->mStore->getPropertyValues( $this->mResult,
					new SMWDIProperty( '_INST' ), $this->getRequestOptions( false ) );
				self::$catCacheObj = $this->mResult->getHash();
				
				$limit = $this->mPrintRequest->getParameter( 'limit' );
				$this->mContent = ( $limit === false ) ? ( self::$catCache ) :
					array_slice( self::$catCache, 0, $limit );
			break;
			case SMWPrintRequest::PRINT_PROP:
				$propertyValue = $this->mPrintRequest->getData();
				if ( $propertyValue->isValid() && $propertyValue->canUse() ) {
					$this->mContent = $this->mStore->getPropertyValues( $this->mResult,
						$propertyValue->getDataItem(), $this->getRequestOptions() );
				} else {
					$this->mContent = array();
				}

				// Print one component of a multi-valued string.
				// Known limitation: the printrequest still is of type _rec, so if printers check
				// for this then they will not recognize that it returns some more concrete type.
				if ( ( $this->mPrintRequest->getTypeID() == '_rec' ) && 
				     ( $this->mPrintRequest->getParameter( 'index' ) !== false ) ) {
					$pos = $this->mPrintRequest->getParameter( 'index' ) - 1;
					$newcontent = array();

					foreach ( $this->mContent as $diContainer ) {
						/* SMWRecordValue */ $recordValue = \SMW\DataValueFactory::getInstance()->newDataItemValue( $diContainer, $propertyValue->getDataItem() );
						$dataItems = $recordValue->getDataItems();
						
						if ( array_key_exists( $pos, $dataItems ) &&
							( !is_null( $dataItems[$pos] ) ) ) {
							$newcontent[] = $dataItems[$pos];
						}
					}

					$this->mContent = $newcontent;
				}
			break;
			case SMWPrintRequest::PRINT_CCAT: ///NOTE: The limit is ignored here.
				if ( self::$catCacheObj != $this->mResult->getHash() ) {
					self::$catCache = $this->mStore->getPropertyValues( $this->mResult, new SMWDIProperty( '_INST' ) );
					self::$catCacheObj = $this->mResult->getHash();
				}

				$found = false;
				$prkey = $this->mPrintRequest->getData()->getDBkey();

				foreach ( self::$catCache as $cat ) {
					if ( $cat->getDBkey() == $prkey ) {
						$found = true;
						break;
					}
				}
				$this->mContent = array( new SMWDIBoolean( $found ) );
			break;
			default: $this->mContent = array(); // Unknown print request.
		}
		
		reset( $this->mContent );
		
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
