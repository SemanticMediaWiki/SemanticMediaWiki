<?php

use SMW\HashBuilder;
use SMW\Query\PrintRequest;
use SMW\Query\QueryLinker;
use SMW\Query\TemporaryEntityListAccumulator;
use SMW\SerializerFactory;

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
 *
 *
 * @ingroup SMWQuery
 *
 * @licence GNU GPL v2 or later
 * @author Markus Krötzsch
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SMWQueryResult {
	/**
	 * Array of SMWDIWikiPage objects that are the basis for this result
	 * @var SMWDIWikiPage[]
	 */
	protected $mResults;

	/**
	 * Array of SMWPrintRequest objects, indexed by their natural hash keys
	 *
	 * @var PrintRequest[]
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
	 * Holds a value that belongs to a count query result
	 * @var integer|null
	 */
	private $countValue;

	/**
	 * Indicates whether results have been retrieved from cache or not
	 *
	 * @var boolean
	 */
	private $isFromCache = false;

	/**
	 * @var TemporaryEntityListAccumulator
	 */
	private $temporaryEntityListAccumulator;

	/**
	 * Initialise the object with an array of SMWPrintRequest objects, which
	 * define the structure of the result "table" (one for each column).
	 *
	 * TODO: Update documentation
	 *
	 * @param PrintRequest[] $printRequests
	 * @param SMWQuery $query
	 * @param SMWDIWikiPage[] $results
	 * @param SMWStore $store
	 * @param boolean $furtherRes
	 */
	public function __construct( array $printRequests, SMWQuery $query, array $results, SMWStore $store, $furtherRes = false ) {
		$this->mResults = $results;
		reset( $this->mResults );
		$this->mPrintRequests = $printRequests;
		$this->mFurtherResults = $furtherRes;
		$this->mQuery = $query;
		$this->mStore = $store;
		$this->temporaryEntityListAccumulator = new TemporaryEntityListAccumulator( $query );
	}

	/**
	 * @since  2.4
	 *
	 * @return TemporaryEntityListAccumulator
	 */
	public function getEntityListAccumulator() {
		return $this->temporaryEntityListAccumulator;
	}

	/**
	 * @since  2.4
	 *
	 * @param boolean $isFromCache
	 */
	public function setFromCache( $isFromCache ) {
		$this->isFromCache = (bool)$isFromCache;
	}

	/**
	 * @since  2.4
	 *
	 * @return boolean
	 */
	public function isFromCache() {
		return $this->isFromCache;
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
	 * Return the next result row as an array of SMWResultArray objects, and
	 * advance the internal pointer.
	 *
	 * @return SMWResultArray[]|false
	 */
	public function getNext() {
		$page = current( $this->mResults );
		next( $this->mResults );

		if ( $page === false ) {
			return false;
		}

		$row = array();

		foreach ( $this->mPrintRequests as $p ) {
			$resultArray = new SMWResultArray( $page, $p, $this->mStore );
			$resultArray->setEntityListAccumulator( $this->temporaryEntityListAccumulator );
			$row[] = $resultArray;
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
	 * Return an array of SMWDIWikiPage objects that make up the
	 * results stored in this object.
	 *
	 * @return SMWDIWikiPage[]
	 */
	public function getResults() {
		return $this->mResults;
	}

	/**
	 * @since 2.3
	 */
	public function reset() {
		return reset( $this->mResults );
	}

	/**
	 * Returns the query object of the current result set
	 *
	 * @since 1.8
	 *
	 * @return SMWQuery
	 */
	public function getQuery() {
		return $this->mQuery;
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
	 * @return PrintRequest[]
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
	 * @since  2.0
	 *
	 * @param integer $countValue
	 */
	public function setCountValue( $countValue ) {
		$this->countValue = (int)$countValue;
	}

	/**
	 * @since  2.0
	 *
	 * @return integer|null
	 */
	public function getCountValue() {
		return $this->countValue;
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
	 * @deprecated since SMW 1.8
	 *
	 * @param string|false $caption
	 *
	 * @return SMWInfolink
	 */
	public function getQueryLink( $caption = false ) {

		$link = QueryLinker::get( $this->mQuery );

		$link->setCaption( $caption );
		$link->setParameter( $this->mQuery->getOffset() + count( $this->mResults ), 'offset' );

		return $link;
	}

	/**
	 * Returns an SMWInfolink object with the QueryResults print requests as parameters.
	 *
	 * @since 1.8
	 *
	 * @return SMWInfolink
	 */
	public function getLink() {
		$params = array( trim( $this->mQuery->getQueryString() ) );

		foreach ( $this->mQuery->getExtraPrintouts() as $printout ) {
			$serialization = $printout->getSerialisation();

			// TODO: this is a hack to get rid of the mainlabel param in case it was automatically added
			// by SMWQueryProcessor::addThisPrintout. Should be done nicer when this link creation gets redone.
			if ( $serialization !== '?#' ) {
				$params[] = $serialization;
			}
		}

		// Note: the initial : prevents SMW from reparsing :: in the query string.
		return SMWInfolink::newInternalLink( '', ':Special:Ask', false, $params );
	}

	/**
	 * @see DISerializer::getSerializedQueryResult
	 * @since 1.7
	 * @return array
	 */
	public function serializeToArray() {

		$serializerFactory = new SerializerFactory();
		$serialized = $serializerFactory->newQueryResultSerializer()->serialize( $this );

		reset( $this->mResults );
		return $serialized;
	}

	/**
	 * Returns a serialized SMWQueryResult object with additional meta data
	 *
	 * This methods extends the serializeToArray() for additional meta
	 * that are useful when handling data via the api
	 *
	 * @note should be used instead of SMWQueryResult::serializeToArray()
	 * as this method contains additional informaion
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function toArray() {

		$time = microtime( true );

		// @note micro optimization: We call getSerializedQueryResult()
		// only once and create the hash here instead of calling getHash()
		// to avoid getSerializedQueryResult() being called again
		// @note count + offset equals total therefore we deploy both values
		$serializeArray = $this->serializeToArray();

		return array_merge( $serializeArray, array(
			'meta'=> array(
				'hash'   => HashBuilder::createHashIdForContent( $serializeArray ),
				'count'  => $this->getCount(),
				'offset' => $this->mQuery->getOffset(),
				'source' => $this->mQuery->getQuerySource(),
				'time'   => number_format( ( microtime( true ) - $time ), 6, '.', '' )
				)
			)
		);
	}

	/**
	 * Returns result hash value
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public function getHash() {
		return HashBuilder::createHashIdForContent( $this->serializeToArray() );
	}

}
