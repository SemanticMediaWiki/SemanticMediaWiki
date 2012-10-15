<?php

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
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 *
 * @file SMW_QueryResult.php
 * @ingroup SMWQuery
 *
 * @licence GNU GPL v2 or later
 * @author Markus Krötzsch
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SMWQueryResult {
	/**
	 * Array of SMWDIWikiPage objects that are the basis for this result
	 * @var Array of SMWDIWikiPage
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
	 * @param array of SMWDIWikiPage $results
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
	 * @return array of SMWResultArray or false
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
	 * Return an array of SMWDIWikiPage objects that make up the
	 * results stored in this object.
	 * 
	 * @return array of SMWDIWikiPage
	 */
	public function getResults() {
		return $this->mResults;
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
	 * @deprecated since SMW 1.8
	 *
	 * @param string|false $caption
	 * 
	 * @return SMWInfolink
	 */
	public function getQueryLink( $caption = false ) {
		$link = $this->getLink();

		if ( $caption == false ) {
			// The space is right here, not in the QPs!
			$caption = ' ' . wfMessage( 'smw_iq_moreresults' )->inContentLanguage()->text();
		}

		$link->setCaption( $caption );

		$params = array( trim( $this->mQuery->getQueryString() ) );

		foreach ( $this->mQuery->getExtraPrintouts() as /* SMWPrintRequest */ $printout ) {
			$serialization = $printout->getSerialisation();

			// TODO: this is a hack to get rid of the mainlabel param in case it was automatically added
			// by SMWQueryProcessor::addThisPrintout. Should be done nicer when this link creation gets redone.
			if ( $serialization !== '?#' ) {
				$params[] = $serialization;
			}
		}

		if ( $this->mQuery->getMainLabel() !== false ) {
			$params['mainlabel'] = $this->mQuery->getMainLabel();
		}

		$params['offset'] = $this->mQuery->getOffset() + count( $this->mResults );

		if ( $params['offset'] === 0 ) {
			unset( $params['offset'] );
		}

		if ( $this->mQuery->getLimit() > 0 ) {
			$params['limit'] = $this->mQuery->getLimit();
		}

		if ( count( $this->mQuery->sortkeys ) > 0 ) {
			$order = implode( ',', $this->mQuery->sortkeys );
			$sort = implode( ',', array_keys( $this->mQuery->sortkeys ) );

			if ( $sort !== '' || $order != 'ASC' ) {
				$params['order'] = $order;
				$params['sort'] = $sort;
			}
		}

		foreach ( $params as $key => $param ) {
			$link->setParameter( $param, is_string( $key ) ? $key : false );
		}

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

		foreach ( $this->mQuery->getExtraPrintouts() as /* SMWPrintRequest */ $printout ) {
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
	 * @see SMWDISerializer::getSerializedQueryResult
	 * @since 1.7
	 * @return array
	 */
	public function serializeToArray() {
		return SMWDISerializer::getSerializedQueryResult( $this );
	}
}
