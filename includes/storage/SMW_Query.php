<?php
/**
 * This file contains the class for representing queries in SMW, each
 * consisting of a query description and possible query parameters.
 * @file
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
	public $sortkeys = array(); // format: "Property name" => "ASC" / "DESC" (note: order of entries also matters)
	public $querymode = SMWQuery::MODE_INSTANCES;

	protected $m_limit;
	protected $m_offset = 0;
	protected $m_description;
	protected $m_errors = array(); // keep any errors that occurred so far
	protected $m_querystring = false; // string (inline query) version (if fixed and known)
	protected $m_inline; // query used inline? (required for finding right default parameters)
	protected $m_concept; // query used in concept? (required for finding right default parameters)
	protected $m_extraprintouts = array(); // SMWPrintoutRequest objects supplied outside querystring
	protected $m_mainlabel = ''; // Since 1.6
	
	/**
	 * Constructor.
	 * @param $description Optional SMWDescription object describing the query conditions
	 * @param $inline bool stating whether this query runs in an inline context; used to determine
	 * proper default parameters (e.g. the default limit)
	 * @param $concept bool stating whether this query belongs to a concept; used to determine
	 * proper default parameters (concepts usually have less restrictions)
	 */
	public function __construct( $description = null, $inline = false, $concept = false ) {
		global $smwgQMaxLimit, $smwgQMaxInlineLimit;
		$this->m_limit = $inline ? $smwgQMaxInlineLimit : $smwgQMaxLimit;
		$this->m_inline = $inline;
		$this->m_concept = $concept;
		$this->m_description = $description;
		$this->applyRestrictions();
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
		$this->m_description = $description;
		foreach ( $this->m_extraprintouts as $printout ) {
			$this->m_description->addPrintRequest( $printout );
		}
		$this->applyRestrictions();
	}

	public function getDescription() {
		return $this->m_description;
	}

	public function setExtraPrintouts( $extraprintouts ) {
		$this->m_extraprintouts = $extraprintouts;
		
		if ( !is_null( $this->m_description ) ) {
			foreach ( $extraprintouts as $printout ) {
				$this->m_description->addPrintRequest( $printout );
			}
		}
	}

	public function getExtraPrintouts() {
		return $this->m_extraprintouts;
	}

	public function getErrors() {
		return $this->m_errors;
	}

	public function addErrors( $errors ) {
		$this->m_errors = array_merge( $this->m_errors, $errors );
	}

	public function setQueryString( $querystring ) {
		$this->m_querystring = $querystring;
	}

	public function getQueryString() {
		if ( $this->m_querystring !== false ) {
			return $this->m_querystring;
		} elseif ( !is_null( $this->m_description ) ) {
			return $this->m_description->getQueryString();
		} else {
			return '';
		}
	}

	public function getOffset() {
		return $this->m_offset;
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
 		$this->m_offset = min( $smwgQMaxLimit, $offset ); // select integer between 0 and maximal limit;
		$this->m_limit = min( $smwgQMaxLimit - $this->m_offset, $this->m_limit ); // note that limit might become 0 here
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
	 * @note It makes sense to have limit==0, e.g. to only show a link to the search special
	 */
	public function setLimit( $limit, $restrictinline = true ) {
		global $smwgQMaxLimit, $smwgQMaxInlineLimit;
		$maxlimit = ( $this->m_inline && $restrictinline ) ? $smwgQMaxInlineLimit : $smwgQMaxLimit;
		$this->m_limit = min( $smwgQMaxLimit - $this->m_offset, $limit, $maxlimit );
		return $this->m_limit;
	}

	/**
	 * Apply structural restrictions to the current description.
	 */
	public function applyRestrictions() {
		global $smwgQMaxSize, $smwgQMaxDepth, $smwgQConceptMaxSize, $smwgQConceptMaxDepth;
		
		if ( !is_null( $this->m_description ) ) {
			if ( $this->m_concept ) {
				$maxsize = $smwgQConceptMaxSize;
				$maxdepth = $smwgQConceptMaxDepth;
			} else {
				$maxsize = $smwgQMaxSize;
				$maxdepth = $smwgQMaxDepth;
			}
			
			$log = array();
			$this->m_description = $this->m_description->prune( $maxsize, $maxdepth, $log );
			
			if ( count( $log ) > 0 ) {
				$this->m_errors[] = wfMsgForContent( 'smw_querytoolarge', str_replace( '[', '&#x005B;', implode( ', ' , $log ) ) );
			}
		}
	}

}
