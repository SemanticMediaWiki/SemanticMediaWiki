<?php

namespace SMW\Query\Result;

use SMW\DataValueFactory;
use SMW\Query\PrintRequest;
use SMW\Query\QueryToken;
use SMW\RequestOptions;
use SMWDataItem as DataItem;
use SMW\DIWikiPage;
use SMW\Store;
use SMWDataValue;
use SMWQueryResult as QueryResult;

/**
 * Container for the contents of a single result field of a query result,
 * i.e. basically an array of SMWDataItems with some additional parameters.
 * The content of the array is fetched on demand only.
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ResultArray {

	/**
	 * @var PrintRequest
	 */
	private $printRequest;

	/**
	 * @var DIWikiPage
	 */
	private $result;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var DataItem[]|false
	 */
	private $content;

	/**
	 * @var ItemJournal
	 */
	private $itemJournal;

	/**
	 * @var FieldItemFinder
	 */
	private $fieldItemFinder;

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
	 * @param DIWikiPage $resultPage
	 * @param PrintRequest $printRequest
	 * @param QueryResult $queryResult
	 *
	 * @return self
	 */
	public static function factory( DIWikiPage $resultPage, PrintRequest $printRequest, QueryResult $queryResult ) {

		$resultArray = new self(
			$resultPage,
			$printRequest,
			$queryResult->getStore(),
			$queryResult->getFieldItemFinder()
		);

		$query = $queryResult->getQuery();

		$resultArray->setQueryToken( $query->getQueryToken() );
		$resultArray->setContextPage( $query->getContextPage() );

		return $resultArray;
	}

	/**
	 * @param DIWikiPage $resultPage
	 * @param PrintRequest $printRequest
	 * @param Store $store
	 * @param fieldItemFinder|null $fieldItemFinder
	 */
	public function __construct( DIWikiPage $resultPage, PrintRequest $printRequest, Store $store, FieldItemFinder $fieldItemFinder = null ) {
		$this->result = $resultPage;
		$this->printRequest = $printRequest;
		$this->store = $store;
		$this->content = false;

		// FIXME 3.0; Inject the object
		$this->fieldItemFinder = $fieldItemFinder;

		if ( $this->fieldItemFinder === null ) {
			$this->fieldItemFinder = new FieldItemFinder( $store );
		}
	}

	/**
	 * Get the SMWStore object that this result is based on.
	 *
	 * @return Store
	 */
	public function getStore() {
		return $this->store;
	}

	/**
	 * Returns the DIWikiPage object to which this ResultArray refers.
	 * If you only care for those objects, consider using SMWQueryResult::getResults()
	 * directly.
	 *
	 * @return DIWikiPage
	 */
	public function getResultSubject() {
		return $this->result;
	}

	/**
	 * Temporary track what entities are used while being instantiated, so an external
	 * service can have access to the list without requiring to resolve the objects
	 * independently.
	 *
	 * @since  2.4
	 *
	 * @param ItemJournal $itemJournal
	 */
	public function setItemJournal( ItemJournal $itemJournal ) {
		$this->itemJournal = $itemJournal;
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
	 * @return DataItem[]|false
	 */
	public function getContent() {
		$this->loadContent();
		return $this->content;
	}

	/**
	 * Return a PrintRequest object describing what is contained in this
	 * result set.
	 *
	 * @return PrintRequest
	 */
	public function getPrintRequest() {
		return $this->printRequest;
	}

	/**
	 * Return the next SMWDataItem object or false if no further object exists.
	 *
	 * @since 1.6
	 *
	 * @return DataItem|false
	 */
	public function getNextDataItem() {
		$this->loadContent();
		$result = current( $this->content );

		if ( $this->itemJournal !== null && $result instanceof DataItem ) {
			$this->itemJournal->recordItem( $result );
		}

		next( $this->content );

		return $result;
	}

	/**
	 * Set the internal pointer of the array of SMWDataItem objects to its first
	 * element. Return the first SMWDataItem object or false if the array is
	 * empty.
	 *
	 * @since 1.7.1
	 *
	 * @return DataItem|false
	 */
	public function reset() {
		$this->loadContent();
		return reset( $this->content );
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
			$contextPage = $this->result;
		}

		if ( $this->printRequest->getMode() == PrintRequest::PRINT_PROP &&
		    strpos( $this->printRequest->getTypeID(), '_rec' ) !== false &&
		    $this->printRequest->getParameter( 'index' ) !== false ) {

			/**
			 * @var \SMWRecordValue $recordValue
			 */
			$recordValue = DataValueFactory::getInstance()->newDataValueByItem(
				$dataItem,
				$this->printRequest->getData()->getDataItem(),
				false,
				$contextPage
			);

			$diProperty = $recordValue->getPropertyDataItemByIndex(
				$this->printRequest->getParameter( 'index' )
			);
		} elseif ( $this->printRequest->isMode( PrintRequest::PRINT_PROP ) ) {
			$diProperty = $this->printRequest->getData()->getDataItem();
		} elseif ( $this->printRequest->isMode( PrintRequest::PRINT_CHAIN ) ) {
			$diProperty = $this->printRequest->getData()->getLastPropertyChainValue()->getDataItem();
		} else {
			$diProperty = null;
		}

		$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
			$dataItem,
			$diProperty,
			false,
			$contextPage
		);

		if ( $this->printRequest->getOutputFormat() ) {
			$dataValue->setOutputFormat( $this->printRequest->getOutputFormat() );
		}

		if ( $this->itemJournal !== null && $dataItem instanceof DataItem ) {
			$this->itemJournal->recordItem( $dataItem );
			$this->itemJournal->recordProperty( $diProperty );
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
		}

		return false;
	}

	/**
	 * Load results of the given print request and result subject. This is only
	 * done when needed.
	 */
	protected function loadContent() {

		if ( $this->content !== false ) {
			return;
		}

		$this->fieldItemFinder->setPrintRequest(
			$this->printRequest
		);

		$this->fieldItemFinder->setQueryToken(
			$this->queryToken
		);

		$this->content = $this->fieldItemFinder->findFor(
			$this->result
		);

		return reset( $this->content );
	}

}
