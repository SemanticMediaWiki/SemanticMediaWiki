<?php

namespace SMW\SQLStore;

use MediaWiki\Title\Title;
use SMW\DataItems\Concept;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\DataValues\WikiPageValue;
use SMW\ProcessingErrorMsgHandler;
use SMW\SQLStore\QueryEngine\ConceptQuerySegmentBuilder;
use Wikimedia\Rdbms\Platform\ISQLPlatform;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ConceptCache {

	private int $upperLimit = 50;

	/**
	 * @since 2.2
	 */
	public function __construct(
		private readonly SQLStore $store,
		private readonly ConceptQuerySegmentBuilder $conceptQuerySegmentBuilder,
	) {
	}

	/**
	 * @since 2.2
	 *
	 * @param int $upperLimit
	 */
	public function setUpperLimit( $upperLimit ): void {
		$this->upperLimit = (int)$upperLimit;
	}

	/**
	 * Refresh the concept cache for the given concept.
	 *
	 * @since 1.8
	 *
	 * @param $concept Title
	 *
	 * @return array of error strings (empty if no errors occurred)
	 */
	public function refreshConceptCache( Title $concept ): array {
		$errors = array_merge(
			$this->conceptQuerySegmentBuilder->getErrors(),
			$this->refresh( $concept )
		);

		$this->conceptQuerySegmentBuilder->cleanUp();

		return ProcessingErrorMsgHandler::normalizeAndDecodeMessages( $errors );
	}

	/**
	 * Delete the concept cache for the given concept.
	 *
	 * @param $concept Title
	 */
	public function deleteConceptCache( Title $concept ): void {
		$this->delete( $concept );
	}

	/**
	 * @param Title $concept
	 *
	 * @return string[] array with error messages
	 */
	public function refresh( Title $concept ): array {
		$db = $this->store->getConnection();

		$cid = $this->store->smwIds->getSMWPageID( $concept->getDBkey(), SMW_NS_CONCEPT, '', '' );
		$cid_c = $this->getIdOfConcept( $concept );

		if ( $cid !== $cid_c ) {
			return [ "Skipping redirect concept." ];
		}

		$conceptQueryText = $this->getConceptCacheText( $concept );

		if ( $conceptQueryText === false ) {
			$this->deleteConceptById( $cid );

			return [ "No concept description found." ];
		}

		// Pre-process query:
		$querySegment = $this->conceptQuerySegmentBuilder->getQuerySegmentFrom(
			$conceptQueryText
		);

		if ( $querySegment === null || $querySegment->joinfield === '' || $querySegment->joinTable === '' ) {
			return [];
		}

		// TODO: catch db exception
		$db->delete(
			SQLStore::CONCEPT_CACHE_TABLE,
			[ 'o_id' => $cid ],
			__METHOD__
		);

		$concCacheTableName = $db->tablename( SQLStore::CONCEPT_CACHE_TABLE );

		// MySQL just uses INSERT IGNORE, no extra conditions
		$where = $querySegment->where;

		if ( $db->getType() == 'postgres' ) {
			// PostgresQL: no INSERT IGNORE, check for duplicates explicitly
			// This code doesn't work and has created all sorts of issues therefore use LEFT JOIN instead
			// http://people.planetpostgresql.org/dfetter/index.php?/archives/48-Adding-Only-New-Rows-INSERT-IGNORE,-Done-Right.html
			//	$where = $querySegment->where . ( $querySegment->where ? ' AND ' : '' ) .
			//		"NOT EXISTS (SELECT NULL FROM $concCacheTableName" .
			//		" WHERE {$concCacheTableName}.s_id = {$querySegment->alias}.s_id " .
			//		" AND  {$concCacheTableName}.o_id = {$querySegment->alias}.o_id )";
			$querySegment->from = str_replace( 'INNER JOIN', 'LEFT JOIN', $querySegment->from );
		}

		$db->query( "INSERT " . ( ( $db->getType() == 'postgres' ) ? '' : 'IGNORE ' ) .
			"INTO $concCacheTableName" .
			" SELECT DISTINCT {$querySegment->joinfield} AS s_id, $cid AS o_id FROM " .
			$db->tableName( $querySegment->joinTable ) . " AS {$querySegment->alias}" .
			$querySegment->from .
			( $where ? ' WHERE ' : '' ) . $where . " LIMIT " . $this->upperLimit,
			__METHOD__,
			ISQLPlatform::QUERY_CHANGE_ROWS
		);

		$db->update(
			'smw_fpt_conc',
			[ 'cache_date' => strtotime( "now" ), 'cache_count' => $db->affectedRows() ],
			[ 's_id' => $cid ],
			__METHOD__
		);

		return [];
	}

	/**
	 * @param Title $concept
	 *
	 * @return string
	 */
	public function getConceptCacheText( Title $concept ) {
		$values = $this->store->getPropertyValues(
			WikiPage::newFromTitle( $concept ),
			new Property( '_CONC' )
		);

		/**
		 * @var bool|Concept $di
		 */
		$di = end( $values );
		$conceptQueryText = $di === false ?: $di->getConceptQuery();

		return $conceptQueryText;
	}

	public function delete( Title $concept ): void {
		$this->deleteConceptById( $this->getIdOfConcept( $concept ) );
	}

	/**
	 * @param Title $concept
	 *
	 * @return int
	 */
	private function getIdOfConcept( Title $concept ): int {
		return $this->store->smwIds->getSMWPageID(
			$concept->getDBkey(),
			SMW_NS_CONCEPT,
			'',
			'',
			false
		);
	}

	/**
	 * @param int $conceptId
	 */
	private function deleteConceptById( int $conceptId ): void {
		// TODO: exceptions should be caught

		$db = $this->store->getConnection();

		$db->delete(
			SQLStore::CONCEPT_CACHE_TABLE,
			[ 'o_id' => $conceptId ],
			__METHOD__
		);

		$db->update(
			'smw_fpt_conc',
			[ 'cache_date' => null, 'cache_count' => null ],
			[ 's_id' => $conceptId ],
			__METHOD__
		);
	}

	/**
	 * @param Title|WikiPageValue|WikiPage $concept
	 *
	 * @return Concept|null
	 */
	public function getStatus( $concept ): ?Concept {
		$db = $this->store->getConnection();

		$cid = $this->store->smwIds->getSMWPageID(
			$concept->getDBkey(),
			$concept->getNamespace(),
			'',
			'',
			false
		);

		// TODO: catch db exception

		$row = $db->selectRow(
			'smw_fpt_conc',
			[ 'concept_txt', 'concept_features', 'concept_size', 'concept_depth', 'cache_date', 'cache_count' ],
			[ 's_id' => $cid ],
			__METHOD__
		);

		if ( $row === false ) {
			return null;
		}

		$dataItem = new Concept(
			$concept,
			null,
			$row->concept_features,
			$row->concept_size,
			$row->concept_depth
		);

		if ( $row->cache_date ) {
			$dataItem->setCacheStatus( 'full' );
			$dataItem->setCacheDate( $row->cache_date );
			$dataItem->setCacheCount( $row->cache_count );
		} else {
			$dataItem->setCacheStatus( 'empty' );
		}

		return $dataItem;
	}

}
