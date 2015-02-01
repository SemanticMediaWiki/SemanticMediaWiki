<?php

namespace SMW\SQLStore\QueryEngine;

use DatabaseBase;
use SMW\DIConcept;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMWDataItem;
use SMWQuery;
use SMWQueryParser;
use SMWSQLStore3;
use SMWSQLStore3QueryEngine;
use SMWWikiPageValue;
use Title;

/**
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ConceptCache {

	/**
	 * @var SMWSQLStore3QueryEngine
	 */
	private $queryEngine;

	/**
	 * @var SMWSQLStore3
	 */
	private $store;

	public function __construct( SMWSQLStore3QueryEngine $queryEngine, SMWSQLStore3 $store ) {
		$this->queryEngine = $queryEngine;
		$this->store = $store;
	}

	/**
	 * @param Title $concept
	 *
	 * @return string[] array with error messages
	 */
	public function refresh( Title $concept ) {
		global $smwgQMaxLimit, $wgDBtype;

		$fname = 'SMW::refreshConceptCache';

		$db = $this->store->getConnection();

		$cid = $this->store->smwIds->getSMWPageID( $concept->getDBkey(), SMW_NS_CONCEPT, '', '' );
		$cid_c = $this->getIdOfConcept( $concept );

		if ( $cid !== $cid_c ) {
			return array( "Skipping redirect concept." );
		}

		$conceptQueryText = $this->getConceptCacheText( $concept );

		if ( $conceptQueryText === false ) {
			$this->deleteConceptById( $cid );

			return array( "No concept description found." );
		}

		// Pre-process query:
		$queryPart = $this->queryEngine->resolveQueryTreeForQueryCondition( $conceptQueryText );

		if ( $queryPart === null || $queryPart->joinfield === '' || $queryPart->joinTable === '' ) {
			return array();
		}

		// TODO: catch db exception
		$db->delete(
			SMWSQLStore3::CONCEPT_CACHE_TABLE,
			array( 'o_id' => $cid ),
			__METHOD__
		);

		$concCacheTableName = $db->tablename( SMWSQLStore3::CONCEPT_CACHE_TABLE );

		if ( $wgDBtype == 'postgres' ) { // PostgresQL: no INSERT IGNORE, check for duplicates explicitly
			$where = $queryPart->where . ( $queryPart->where ? ' AND ' : '' ) .
				"NOT EXISTS (SELECT NULL FROM $concCacheTableName" .
				" WHERE {$concCacheTableName}.s_id = {$queryPart->alias}.s_id " .
				" AND  {$concCacheTableName}.o_id = {$queryPart->alias}.o_id )";
		} else { // MySQL just uses INSERT IGNORE, no extra conditions
			$where = $queryPart->where;
		}

		// TODO: catch db exception

		$db->query( "INSERT " . ( ( $wgDBtype == 'postgres' ) ? '' : 'IGNORE ' ) .
			"INTO $concCacheTableName" .
			" SELECT DISTINCT {$queryPart->joinfield} AS s_id, $cid AS o_id FROM " .
			$db->tableName( $queryPart->joinTable ) . " AS {$queryPart->alias}" .
			$queryPart->from .
			( $where ? ' WHERE ' : '' ) . $where . " LIMIT $smwgQMaxLimit",
			$fname );

		$db->update(
			'smw_fpt_conc',
			array( 'cache_date' => strtotime( "now" ), 'cache_count' => $db->affectedRows() ),
			array( 's_id' => $cid ),
			__METHOD__
		);

		return array();
	}

	/**
	 * @param Title $concept
	 *
	 * @return string
	 */
	public function getConceptCacheText( Title $concept ) {
		$values = $this->store->getPropertyValues(
			DIWikiPage::newFromTitle( $concept ),
			new DIProperty( '_CONC' )
		);

		/**
		 * @var bool|DIConcept $di
		 */
		$di = end( $values );
		$conceptQueryText = $di === false ?: $di->getConceptQuery();

		return $conceptQueryText;
	}

	public function delete( Title $concept ) {
		$this->deleteConceptById( $this->getIdOfConcept( $concept ) );
	}

	/**
	 * @param Title $concept
	 *
	 * @return int
	 */
	private function getIdOfConcept( Title $concept ) {
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
	private function deleteConceptById( $conceptId ) {
		// TODO: exceptions should be caught

		$db = $this->store->getConnection();

		$db->delete(
			SMWSQLStore3::CONCEPT_CACHE_TABLE,
			array( 'o_id' => $conceptId ),
			__METHOD__
		);

		$db->update(
			'smw_fpt_conc',
			array( 'cache_date' => null, 'cache_count' => null ),
			array( 's_id' => $conceptId ),
			__METHOD__
		);
	}

	/**
	 * @param Title|SMWWikiPageValue|DIWikiPage $concept
	 *
	 * @return DIConcept|null
	 */
    public function getStatus( $concept ) {
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
			array( 'concept_txt', 'concept_features', 'concept_size', 'concept_depth', 'cache_date', 'cache_count' ),
			array( 's_id' => $cid ),
			__METHOD__
		);

		if ( $row === false ) {
			return null;
		}

		$dataItem = new DIConcept(
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