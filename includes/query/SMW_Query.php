<?php

use SMW\HashBuilder;
use SMW\Query\PrintRequest;
use SMW\DIWikiPage;

/**
 * This file contains the class for representing queries in SMW, each
 * consisting of a query description and possible query parameters.
 * @ingroup SMWQuery
 * @author Markus Krötzsch
 */

/**
 * This group contains all parts of SMW that relate to processing semantic queries.
 * SMW components that relate to plain storage access (for querying or otherwise)
 * have their own group.
 * @defgroup SMWQuery SMWQuery
 * @ingroup SMW
 */

/**
 * Representation of queries in SMW, each consisting of a query
 * description and various parameters. Some settings might also lead to
 * changes in the query description.
 *
 * Most additional query parameters (limit, sort, ascending, ...) are
 * interpreted as in SMWRequestOptions (though the latter contains some
 * additional settings).
 * @ingroup SMWQuery
 */
class SMWQuery {

	const MODE_INSTANCES = 1; // normal instance retrieval
	const MODE_COUNT = 2; // find result count only
	const MODE_DEBUG = 3; // prepare query, but show debug data instead of executing it
	const MODE_NONE = 4;  // do nothing with the query

	public $sort = false;
	public $sortkeys = array(); // format: "Property key" => "ASC" / "DESC" (note: order of entries also matters)
	public $querymode = self::MODE_INSTANCES;

	private $limit;
	private $offset = 0;
	private $description;
	private $errors = array(); // keep any errors that occurred so far
	private $queryString = false; // string (inline query) version (if fixed and known)
	private $isInline; // query used inline? (required for finding right default parameters)
	private $isUsedInConcept; // query used in concept? (required for finding right default parameters)

	/**
	 * @var PrintRequest[]
	 */
	private $m_extraprintouts = array(); // SMWPrintoutRequest objects supplied outside querystring
	private $m_mainlabel = ''; // Since 1.6

	/**
	 * @var DIWikiPage
	 */
	private $subject;

	/**
	 * Constructor.
	 * @param $description SMWDescription object describing the query conditions
	 * @param $inline bool stating whether this query runs in an inline context; used to determine
	 * proper default parameters (e.g. the default limit)
	 * @param $concept bool stating whether this query belongs to a concept; used to determine
	 * proper default parameters (concepts usually have less restrictions)
	 */
	public function __construct( $description = null, $inline = false, $concept = false ) {
		global $smwgQMaxLimit, $smwgQMaxInlineLimit;
		$this->limit = $inline ? $smwgQMaxInlineLimit : $smwgQMaxLimit;
		$this->isInline = $inline;
		$this->isUsedInConcept = $concept;
		$this->description = $description;
		$this->applyRestrictions();
	}

	/**
	 * @since 2.3
	 *
	 * @param DIWikiPage $subject
	 */
	public function setSubject( DIWikiPage $subject ) {
		$this->subject = $subject;
	}

	/**
	 * @since 2.3
	 *
	 * @return DIWikiPage $subject
	 */
	public function getSubject() {
		return $this->subject;
	}

	/**
	 * Sets the mainlabel.
	 *
	 * @since 1.6.
	 *
	 * @param string $mainlabel
	 */
	public function setMainLabel( $mainlabel ) {
		$this->m_mainlabel = $mainlabel;
	}

	/**
	 * Gets the mainlabel.
	 *
	 * @since 1.6.
	 *
	 * @return string
	 */
	public function getMainLabel() {
		return $this->m_mainlabel;
	}

	public function setDescription( SMWDescription $description ) {
		$this->description = $description;
		foreach ( $this->m_extraprintouts as $printout ) {
			$this->description->addPrintRequest( $printout );
		}
		$this->applyRestrictions();
	}

	public function getDescription() {
		return $this->description;
	}

	public function setExtraPrintouts( $extraprintouts ) {
		$this->m_extraprintouts = $extraprintouts;

		if ( !is_null( $this->description ) ) {
			foreach ( $extraprintouts as $printout ) {
				$this->description->addPrintRequest( $printout );
			}
		}
	}

	/**
	 * @return PrintRequest[]
	 */
	public function getExtraPrintouts() {
		return $this->m_extraprintouts;
	}

	public function getErrors() {
		return $this->errors;
	}

	public function addErrors( $errors ) {
		$this->errors = array_merge( $this->errors, $errors );
	}

	public function setQueryString( $querystring ) {
		$this->queryString = $querystring;
	}

	public function getQueryString() {
		if ( $this->queryString !== false ) {
			return $this->queryString;
		} elseif ( !is_null( $this->description ) ) {
			return $this->description->getQueryString();
		} else {
			return '';
		}
	}

	public function getOffset() {
		return $this->offset;
	}

	/**
	 * Set an offset for the returned query results. No offset beyond the maximal query
	 * limit will be set, and the current query limit might be reduced in order to ensure
	 * that no results beyond the maximal limit are returned.
	 * The function returns the chosen offset.
	 * @todo The function should be extended to take into account whether or not we
	 * are in inline mode (not critical, since offsets are usually not applicable inline).
	 */
	public function setOffset( $offset ) {
		global $smwgQMaxLimit;
		$this->offset = min( $smwgQMaxLimit, $offset ); // select integer between 0 and maximal limit;
		$this->limit = min( $smwgQMaxLimit - $this->offset, $this->limit ); // note that limit might become 0 here
		return $this->offset;
	}

	public function getLimit() {
		return $this->limit;
	}

	/**
	 * Set a limit for number of query results. The set limit might be restricted by the
	 * current offset so as to ensure that the number of the last considered result does not
	 * exceed the maximum amount of supported results.
	 * The function returns the chosen limit.
	 * @note It makes sense to have limit==0, e.g. to only show a link to the search special
	 */
	public function setLimit( $limit, $restrictinline = true ) {
		global $smwgQMaxLimit, $smwgQMaxInlineLimit;
		$maxlimit = ( $this->isInline && $restrictinline ) ? $smwgQMaxInlineLimit : $smwgQMaxLimit;
		$this->limit = min( $smwgQMaxLimit - $this->offset, $limit, $maxlimit );
		return $this->limit;
	}

	/**
	 * @note Sets an unbound limit that is independent from GLOBAL settings
	 *
	 * @since 2.0
	 *
	 * @param integer $limit
	 *
	 * @return Query
	 */
	public function setUnboundLimit( $limit ) {
		$this->limit = (int)$limit;
		return $this;
	}

	/**
	 * @note format: "Property key" => "ASC" / "DESC" (note: order of entries also matters)
	 *
	 * @since 2.2
	 *
	 * @param array $sortKeys
	 */
	public function setSortKeys( array $sortKeys ) {
		$this->sortKeys = $sortKeys;
	}

	/**
	 * @since 2.2
	 *
	 * @return array
	 */
	public function getSortKeys() {
		return $this->sortKeys;
	}

	/**
	 * Apply structural restrictions to the current description.
	 */
	public function applyRestrictions() {
		global $smwgQMaxSize, $smwgQMaxDepth, $smwgQConceptMaxSize, $smwgQConceptMaxDepth;

		if ( !is_null( $this->description ) ) {
			if ( $this->isUsedInConcept ) {
				$maxsize = $smwgQConceptMaxSize;
				$maxdepth = $smwgQConceptMaxDepth;
			} else {
				$maxsize = $smwgQMaxSize;
				$maxdepth = $smwgQMaxDepth;
			}

			$log = array();
			$this->description = $this->description->prune( $maxsize, $maxdepth, $log );

			if ( count( $log ) > 0 ) {
				$this->errors[] = wfMessage(
					'smw_querytoolarge',
					str_replace( '[', '&#x005B;', implode( ', ', $log ) )
				)->inContentLanguage()->text();
			}
		}
	}

	/**
	 * Returns serialized query details
	 *
	 * The output is following the askargs api module convention
	 *
	 * conditions The query conditions (requirements for a subject to be included)
	 * printouts The query printouts (which properties to show per subject)
	 * parameters The query parameters (non-condition and non-printout arguments)
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function toArray() {
		$serialized = array();

		$serialized['conditions'] = $this->getQueryString();

		// This can be extended but for the current use cases that is
		// sufficient since most printer related parameters have to be sourced
		// in the result printer class
		$serialized['parameters'] = array(
				'limit'     => $this->limit,
				'offset'    => $this->offset,
				'sortkeys'  => $this->sortkeys,
				'mainlabel' => $this->m_mainlabel,
				'querymode' => $this->querymode
		);

		foreach ( $this->getExtraPrintouts() as $printout ) {
			$serialization = $printout->getSerialisation();
			if ( $serialization !== '?#' ) {
				$serialized['printouts'][] = $serialization;
			}
		}

		return $serialized;
	}

	/**
	 * @since 2.1
	 *
	 * @return string
	 */
	public function getHash() {
		return HashBuilder::createHashIdForContent( $this->toArray() );
	}

	/**
	 * @since 2.3
	 *
	 * @return string
	 */
	public function getQueryId() {
		return '_QUERY' . $this->getHash();
	}

}
