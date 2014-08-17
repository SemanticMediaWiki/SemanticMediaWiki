<?php

namespace SMW\SQLStore\QueryEngine\Compiler;

use SMW\SQLStore\QueryEngine\QueryCompiler;
use SMW\SQLStore\QueryEngine\QueryBuilder;
use SMW\SQLStore\QueryEngine\QueryContainer;

use SMW\Query\Language\ConceptDescription;
use SMW\Query\Language\Description;

use SMW\DIWikiPage;

use SMWQueryParser as QueryParser;
use SMWSQLStore3;

/**
 * @since 2.1
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw
 * @author mwjames
 */
class ConceptDescriptionCompiler implements QueryCompiler {

	/**
	 * @var QueryBuilder
	 */
	private $queryBuilder;

	/**
	 * @since 2.1
	 *
	 * @param QueryBuilder $queryBuilder
	 */
	public function __construct( QueryBuilder $queryBuilder ) {
		$this->queryBuilder = $queryBuilder;
	}

	/**
	 * @since 2.1
	 *
	 * @return boolean
	 */
	public function canCompileDescription( Description $description ) {
		return $description instanceOf ConceptDescription;
	}

	/**
	 * @since 2.1
	 *
	 * @param Description $description
	 *
	 * @return QueryContainer
	 */
	public function compileDescription( Description $description ) {

		$query = new QueryContainer();

		$conceptId = $this->queryBuilder->getStore()->getObjectIds()->getSMWPageID(
			$description->getConcept()->getDBkey(),
			SMW_NS_CONCEPT,
			'',
			''
		);

		$row = $this->getConceptForId( $conceptId );

		if ( $row === false ) { // No description found, concept does not exist.
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

			$query->jointable = SMWSQLStore3::CONCEPT_CACHE_TABLE;
			$query->joinfield = "$query->alias.s_id";
			$query->where = "$query->alias.o_id=" . $this->queryBuilder->getStore()->getDatabase()->addQuotes( $conceptId );
		} elseif ( $row->concept_txt ) { // Parse description and process it recursively.
			if ( $may_be_computed ) {
				$qid = $this->queryBuilder->compileQueries( $this->getConceptQueryDescription( $row->concept_txt ) );

				if ($qid != -1) {
					$query = $this->queryBuilder->getQueryContainer( $qid );
				} else { // somehow the concept query is no longer valid; maybe some syntax changed (upgrade) or global settings were modified since storing it
					$this->queryBuilder->addError( wfMessage( 'smw_emptysubquery' )->text() ); // not quite the right message, but this case is very rare; let us not make detailed messages for this
				}
			} else {
				$this->queryBuilder->addError( wfMessage( 'smw_concept_cache_miss', $description->getConcept()->getText() )->text() );
			}
		} // else: no cache, no description (this may happen); treat like empty concept

		return $query;
	}

	/**
	 * We bypass the storage interface here (which is legal as we control it,
	 * and safe if we are careful with changes ...)
	 *
	 * This should be faster, but we must implement the unescaping that concepts
	 * do on getWikiValue
	 */
	private function getConceptForId( $id ) {
		return $this->queryBuilder->getStore()->getDatabase()->selectRow(
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

		return $queryParser->getQueryDescription( str_replace( array( '&lt;', '&gt;', '&amp;' ), array( '<', '>', '&' ), $conceptQuery ) );
	}

}
