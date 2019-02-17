<?php

use SMW\DataValueFactory;
use SMW\Query\PrintRequest;
use SMW\Query\QueryToken;
use SMW\Query\Result\ResolverJournal;
use SMW\Query\Result\ResultFieldMatchFinder;
use SMWDataItem as DataItem;
use SMW\DIWikiPage;
use SMWQueryResult as QueryResult;

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
	 * @var PrintRequest
	 */
	private $mPrintRequest;

	/**
	 * @var SMWDIWikiPage
	 */
	private $mResult;

	/**
	 * @var SMWStore
	 */
	private $mStore;

	/**
	 * @var SMWDataItem[]|false
	 */
	private $mContent;

	/**
	 * @var ResolverJournal
	 */
	private $resolverJournal;

	/**
	 * @var ResultFieldMatchFinder
	 */
	private $resultFieldMatchFinder;

	/**
	 * @var QueryToken
	 */
	private $queryToken;

	/**
	 * @var DIWikiPage
	 */
	private $contextPage;

	/**
	 * @since 3.1
	 *
	 * @param SMWDIWikiPage $resultPage
	 * @param PrintRequest $printRequest
	 * @param QueryResult $queryResult
	 *
	 * @return ResultArray
	 */
	public static function factory( SMWDIWikiPage $resultPage, PrintRequest $printRequest, QueryResult $queryResult ) {

		$resultArray = new self(
			$resultPage,
			$printRequest,
			$queryResult->getStore()
		);

		$query = $queryResult->getQuery();

		$resultArray->setQueryToken( $query->getQueryToken() );
		$resultArray->setContextPage( $query->getContextPage() );

		return $resultArray;
	}

	/**
	 * Constructor.
	 *
	 * @param SMWDIWikiPage $resultPage
	 * @param PrintRequest $printRequest
	 * @param SMWStore $store
	 */
	public function __construct( SMWDIWikiPage $resultPage, PrintRequest $printRequest, SMWStore $store ) {
		$this->mResult = $resultPage;
		$this->mPrintRequest = $printRequest;
		$this->mStore = $store;
		$this->mContent = false;

		// FIXME 3.0; Inject the object
		$this->resultFieldMatchFinder = new ResultFieldMatchFinder( $store, $printRequest );
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
	 * Temporary track what entities are used while being instantiated, so an external
	 * service can have access to the list without requiring to resolve the objects
	 * independently.
	 *
	 * @since  2.4
	 *
	 * @param ResolverJournal $resolverJournal
	 */
	public function setResolverJournal( ResolverJournal $resolverJournal ) {
		$this->resolverJournal = $resolverJournal;
	}

	/**
	 * @since 2.5
	 *
	 * @param QueryToken|null $queryToken
	 */
	public function setQueryToken( QueryToken $queryToken = null ) {
		$this->queryToken = $queryToken;
	}

	/**
	 * @since 3.1
	 *
	 * @param DIWikiPage|null $contextPage
	 */
	public function setContextPage( DIWikiPage $contextPage = null ) {
		$this->contextPage = $contextPage;
	}

	/**
	 * Returns an array of SMWDataItem objects that contain the results of
	 * the given print request for the given result object.
	 *
	 * @return SMWDataItem[]|false
	 */
	public function getContent() {
		$this->loadContent();
		return $this->mContent;
	}

	/**
	 * Return a PrintRequest object describing what is contained in this
	 * result set.
	 *
	 * @return PrintRequest
	 */
	public function getPrintRequest() {
		return $this->mPrintRequest;
	}

	/**
	 * Return the next SMWDataItem object or false if no further object exists.
	 *
	 * @since 1.6
	 *
	 * @return SMWDataItem|false
	 */
	public function getNextDataItem() {
		$this->loadContent();
		$result = current( $this->mContent );

		if ( $this->resolverJournal !== null && $result instanceof DataItem ) {
			$this->resolverJournal->recordItem( $result );
		}

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
	 * @return SMWDataItem|false
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
	 * @return SMWDataValue|false
	 */
	public function getNextDataValue() {
		$dataItem = $this->getNextDataItem();

		if ( $dataItem === false ) {
			return false;
		}

		$contextPage = $this->contextPage;

		// The context page indicates an embedded query request therefore
		// use this particular context (content language etc.) to guide
		// formatting characteristics
		if ( $contextPage === null ) {
			$contextPage = $this->mResult;
		}

		if ( $this->mPrintRequest->getMode() == PrintRequest::PRINT_PROP &&
		    strpos( $this->mPrintRequest->getTypeID(), '_rec' ) !== false &&
		    $this->mPrintRequest->getParameter( 'index' ) !== false ) {

			$recordValue = DataValueFactory::getInstance()->newDataValueByItem(
				$dataItem,
				$this->mPrintRequest->getData()->getDataItem(),
				false,
				$contextPage
			);

			$diProperty = $recordValue->getPropertyDataItemByIndex(
				$this->mPrintRequest->getParameter( 'index' )
			);
		} elseif ( $this->mPrintRequest->isMode( PrintRequest::PRINT_PROP ) ) {
			$diProperty = $this->mPrintRequest->getData()->getDataItem();
		} elseif ( $this->mPrintRequest->isMode( PrintRequest::PRINT_CHAIN ) ) {
			$diProperty = $this->mPrintRequest->getData()->getLastPropertyChainValue()->getDataItem();
		} else {
			$diProperty = null;
		}

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$dataItem,
			$diProperty,
			false,
			$contextPage
		);

		if ( $this->mPrintRequest->getOutputFormat() ) {
			$dataValue->setOutputFormat( $this->mPrintRequest->getOutputFormat() );
		}

		if ( $this->resolverJournal !== null && $dataItem instanceof DataItem ) {
			$this->resolverJournal->recordItem( $dataItem );
			$this->resolverJournal->recordProperty( $diProperty );
		}

		return $dataValue;
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
	 * @return string|false
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

		if ( $this->mContent !== false ) {
			return;
		}

		$this->resultFieldMatchFinder->setQueryToken(
			$this->queryToken
		);

		$this->mContent = $this->resultFieldMatchFinder->findAndMatch(
			$this->mResult
		);

		return reset( $this->mContent );
	}

	/**
	 * Make a request option object based on the given parameters, and
	 * return NULL if no such object is required. The parameter defines
	 * if the limit should be taken into account, which is not always desired
	 * (especially if results are to be cached for future use).
	 *
	 * @param boolean $useLimit
	 *
	 * @return SMWRequestOptions|null
	 */
	protected function getRequestOptions( $useLimit = true ) {
		return $this->resultFieldMatchFinder->getRequestOptions( $useLimit );
	}

}
