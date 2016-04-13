<?php

namespace SMW\SQLStore\QueryEngine\Interpreter;

use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\Description;
use SMW\SQLStore\QueryEngine\DescriptionInterpreter;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMW\SQLStore\QueryEngine\QuerySegmentListBuilder;
use SMWQueryParser as QueryParser;
use SMWSQLStore3;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class ConceptDescriptionInterpreter implements DescriptionInterpreter {

	/**
	 * @var QuerySegmentListBuilder
	 */
	private $querySegmentListBuilder;

	/**
	 * @since 2.2
	 *
	 * @param QuerySegmentListBuilder $querySegmentListBuilder
	 */
	public function __construct( QuerySegmentListBuilder $querySegmentListBuilder ) {
		$this->querySegmentListBuilder = $querySegmentListBuilder;
	}

	/**
	 * @since 2.2
	 *
	 * @return boolean
	 */
	public function canInterpretDescription( Description $description ) {
		return $description instanceof ConceptDescription;
	}

	/**
	 * @since 2.2
	 *
	 * @param Description $description
	 *
	 * @return QuerySegment
	 */
	public function interpretDescription( Description $description ) {

		$query = new QuerySegment();

		$conceptId = $this->querySegmentListBuilder->getStore()->getObjectIds()->getSMWPageID(
			$description->getConcept()->getDBkey(),
			SMW_NS_CONCEPT,
			'',
			''
		);

		$hash = 'concept-' . $conceptId;

		$this->querySegmentListBuilder->getCircularReferenceGuard()->mark( $hash );

		if ( $this->querySegmentListBuilder->getCircularReferenceGuard()->isCircularByRecursionFor( $hash ) ) {

			$this->querySegmentListBuilder->addError(
				wfMessage( 'smw-query-condition-circular', $description->getQueryString() )->text()
			);

			return $query;
		}

		$db = $this->querySegmentListBuilder->getStore()->getConnection( 'mw.db.queryengine' );
		$row = $this->getConceptForId( $db, $conceptId );

		// No description found, concept does not exist.
		if ( $row === false ) {
			$this->querySegmentListBuilder->getCircularReferenceGuard()->unmark( 'concept-' . $conceptId );
			// keep the above query object, it yields an empty result
			// TODO: announce an error here? (maybe not, since the query processor can check for
			// non-existing concept pages which is probably the main reason for finding nothing here)
			return $query;
		};

		global $smwgQConceptCaching, $smwgQMaxSize, $smwgQMaxDepth, $smwgQFeatures, $smwgQConceptCacheLifetime;

		$may_be_computed = ( $smwgQConceptCaching == CONCEPT_CACHE_NONE ) ||
		    ( ( $smwgQConceptCaching == CONCEPT_CACHE_HARD ) && ( ( ~( ~( $row->concept_features + 0 ) | $smwgQFeatures ) ) == 0 ) &&
		      ( $smwgQMaxSize >= $row->concept_size ) && ( $smwgQMaxDepth >= $row->concept_depth ) );

		if ( $row->cache_date &&
		     ( ( $row->cache_date > ( strtotime( "now" ) - $smwgQConceptCacheLifetime * 60 ) ) ||
		       !$may_be_computed ) ) { // Cached concept, use cache unless it is dead and can be revived.

			$query->joinTable = SMWSQLStore3::CONCEPT_CACHE_TABLE;
			$query->joinfield = "$query->alias.s_id";
			$query->where = "$query->alias.o_id=" . $db->addQuotes( $conceptId );
		} elseif ( $row->concept_txt ) { // Parse description and process it recursively.
			if ( $may_be_computed ) {
				$qid = $this->querySegmentListBuilder->buildQuerySegmentFor( $this->getConceptQueryDescription( $row->concept_txt ) );

				if ($qid != -1) {
					$query = $this->querySegmentListBuilder->findQuerySegment( $qid );
				} else { // somehow the concept query is no longer valid; maybe some syntax changed (upgrade) or global settings were modified since storing it
					$this->querySegmentListBuilder->addError( wfMessage( 'smw_emptysubquery' )->text() ); // not the right message, but this case is very rare; let us not make detailed messages for this
				}
			} else {
				$this->querySegmentListBuilder->addError(
					wfMessage( 'smw_concept_cache_miss', $description->getConcept()->getTitle()->getText() )->text()
				);
			}
		} // else: no cache, no description (this may happen); treat like empty concept

		$this->querySegmentListBuilder->getCircularReferenceGuard()->unmark( $hash );

		return $query;
	}

	/**
	 * We bypass the storage interface here (which is legal as we control it,
	 * and safe if we are careful with changes ...)
	 *
	 * This should be faster, but we must implement the unescaping that concepts
	 * do on getWikiValue
	 */
	private function getConceptForId( $db, $id ) {
		return $db->selectRow(
			'smw_fpt_conc',
			array( 'concept_txt', 'concept_features', 'concept_size', 'concept_depth', 'cache_date' ),
			array( 's_id' => $id ),
			__METHOD__
		);
	}

	/**
	 * No defaultnamespaces here; If any, these are already in the concept.
	 * Unescaping is the same as in SMW_DV_Conept's getWikiValue().
	 */
	private function getConceptQueryDescription( $conceptQuery ) {
		$queryParser = new QueryParser();

		return $queryParser->getQueryDescription(
			str_replace( array( '&lt;', '&gt;', '&amp;' ), array( '<', '>', '&' ), $conceptQuery )
		);
	}

}
