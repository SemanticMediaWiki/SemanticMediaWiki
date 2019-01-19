<?php

namespace SMW\SQLStore\QueryEngine\DescriptionInterpreters;

use RuntimeException;
use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Description;
use SMW\Query\Language\Disjunction;
use SMW\Query\Parser as QueryParser;
use SMW\SQLStore\QueryEngine\DescriptionInterpreter;
use SMW\SQLStore\QueryEngine\QuerySegment;
use SMW\SQLStore\QueryEngine\ConditionBuilder;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Utils\CircularReferenceGuard;

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
	 * @var Store
	 */
	private $store;

	/**
	 * @var ConditionBuilder
	 */
	private $conditionBuilder;

	/**
	 * @var CircularReferenceGuard
	 */
	private $circularReferenceGuard;

	/**
	 * @var QueryParser
	 */
	private $queryParser;

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 * @param ConditionBuilder $conditionBuilder
	 * @param CircularReferenceGuard $circularReferenceGuard
	 */
	public function __construct( Store $store, ConditionBuilder $conditionBuilder, CircularReferenceGuard $circularReferenceGuard ) {
		$this->store = $store;
		$this->conditionBuilder = $conditionBuilder;
		$this->circularReferenceGuard = $circularReferenceGuard;
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
	 * @since 3.0
	 *
	 * @param QueryParser $queryParser
	 */
	public function setQueryParser( QueryParser $queryParser ) {
		$this->queryParser = $queryParser;
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
		$concept = $description->getConcept();

		$id = $this->store->getObjectIds()->getSMWPageID(
			$concept->getDBkey(),
			SMW_NS_CONCEPT,
			'',
			''
		);

		$hash = 'concept-' . $id;

		$this->circularReferenceGuard->mark( $hash );

		if ( $this->circularReferenceGuard->isCircular( $hash ) ) {

			$this->conditionBuilder->addError(
				[ 'smw-query-condition-circular', $description->getQueryString() ]
			);

			return $query;
		}

		$connection = $this->store->getConnection( 'mw.db.queryengine' );
		$row = $this->findConceptById( $connection, $id );

		// No description found, concept does not exist.
		if ( $row === false ) {
			$this->circularReferenceGuard->unmark( 'concept-' . $id );
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

			$query->joinTable = SQLStore::CONCEPT_CACHE_TABLE;
			$query->joinfield = "$query->alias.s_id";
			$query->where = "$query->alias.o_id=" . $connection->addQuotes( $id );
		} elseif ( $row->concept_txt ) { // Parse description and process it recursively.
			if ( $may_be_computed ) {
				$description = $this->buildDescription( $row->concept_txt );

				$this->findCircularDescription(
					$concept,
					$description
				);

				$qid = $this->conditionBuilder->buildFromDescription(
					$description
				);

				if ($qid != -1) {
					$query = $this->conditionBuilder->findQuerySegment( $qid );
				} else { // somehow the concept query is no longer valid; maybe some syntax changed (upgrade) or global settings were modified since storing it
					$this->conditionBuilder->addError( 'smw_emptysubquery' ); // not the right message, but this case is very rare; let us not make detailed messages for this
				}
			} else {
				$this->conditionBuilder->addError(
					[ 'smw_concept_cache_miss', $concept->getDBkey() ]
				);
			}
		} // else: no cache, no description (this may happen); treat like empty concept

		$this->circularReferenceGuard->unmark( $hash );

		return $query;
	}

	/**
	 * We bypass the storage interface here (which is legal as we control it,
	 * and safe if we are careful with changes ...)
	 *
	 * This should be faster, but we must implement the unescaping that concepts
	 * do on getWikiValue
	 */
	private function findConceptById( $connection, $id ) {
		return $connection->selectRow(
			'smw_fpt_conc',
			[ 'concept_txt', 'concept_features', 'concept_size', 'concept_depth', 'cache_date' ],
			[ 's_id' => $id ],
			__METHOD__
		);
	}

	/**
	 * No defaultnamespaces here; If any, these are already in the concept.
	 * Unescaping is the same as in SMW_DV_Conept's getWikiValue().
	 */
	private function buildDescription( $conceptQuery ) {

		if ( $this->queryParser === null ) {
			throw new RuntimeException( 'Missing a QueryParser instance' );
		}

		return $this->queryParser->getQueryDescription(
			str_replace( [ '&lt;', '&gt;', '&amp;' ], [ '<', '>', '&' ], $conceptQuery )
		);
	}

	private function findCircularDescription( $concept, &$description ) {

		if ( $description instanceof ConceptDescription ) {
			if ( $description->getConcept()->equals( $concept ) ) {
				$this->conditionBuilder->addError(
					[ 'smw-query-condition-circular', $description->getQueryString() ]
				);
				return;
			}
		}

		if ( $description instanceof Conjunction || $description instanceof Disjunction ) {
			foreach ( $description->getDescriptions() as $desc ) {
				$this->findCircularDescription( $concept, $desc );
			}
		}
	}

}
