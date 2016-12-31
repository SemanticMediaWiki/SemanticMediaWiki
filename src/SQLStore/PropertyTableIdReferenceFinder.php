<?php

namespace SMW\SQLStore;

use SMW\DIProperty;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class PropertyTableIdReferenceFinder {

	/**
	 * @var SQLStore
	 */
	private $store = null;

	/**
	 * @var Database
	 */
	private $connection = null;

	/**
	 * @var boolean
	 */
	private $isCapitalLinks = true;

	/**
	 * @since 2.4
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
		$this->connection = $this->store->getConnection( 'mw.db' );
	}

	/**
	 * @note If $wgCapitalLinks is set false then it will avoid forcing the first
	 * letter of page titles (including included pages, images and categories)
	 * to capitals
	 *
	 * @since 2.4
	 *
	 * @param booelan $isCapitalLinks
	 */
	public function isCapitalLinks( $isCapitalLinks ) {
		$this->isCapitalLinks = $isCapitalLinks;
	}

	/**
	 * @since 2.4
	 *
	 * @param DIProperty $property
	 *
	 * @return DataItem|false
	 */
	public function tryToFindAtLeastOneReferenceForProperty( DIProperty $property ) {

		$dataItem = $property->getDiWikiPage();

		$sid = $this->store->getObjectIds()->getSMWPageID(
			$dataItem->getDBkey(),
			$dataItem->getNamespace(),
			$dataItem->getInterwiki(),
			''
		);

		// Lets see if we have some lower/upper case matching for
		// when wgCapitalLinks setting was involved
		if ( !$this->isCapitalLinks && $sid == 0 ) {
			$sid = $this->store->getObjectIds()->getSMWPageID(
				lcfirst( $dataItem->getDBkey() ),
				$dataItem->getNamespace(),
				$dataItem->getInterwiki(),
				''
			);
		}

		return $this->tryToFindAtLeastOneReferenceForId( $sid );
	}

	/**
	 * @since 2.4
	 *
	 * @param integer $id
	 *
	 * @return boolean
	 */
	public function hasResidualReferenceForId( $id ) {

		if ( $id == SQLStore::FIXED_PROPERTY_ID_UPPERBOUND ) {
			return true;
		}

		return (bool)$this->tryToFindAtLeastOneReferenceForId( $id );
	}

	/**
	 * @since 2.5
	 *
	 * @param integer $id
	 *
	 * @return array
	 */
	public function searchAllTablesToFindAtLeastOneReferenceById( $id ) {

		$references = array();

		foreach ( $this->store->getPropertyTables() as $proptable ) {
			$reference = false;

			if ( ( $reference = $this->findReferenceByPropertyTable( $proptable, $id ) ) !== false ) {
				$references[$proptable->getName()] = $reference;
			}
		}

		if ( ( $reference = $this->findReferenceByQueryLinksTable( $id ) ) !== false ) {
			$references[SQLStore::QUERY_LINKS_TABLE] = $reference;
		}

		return $references;
	}

	/**
	 * @since 2.4
	 *
	 * @param integer $id
	 *
	 * @return DataItem|false
	 */
	public function tryToFindAtLeastOneReferenceForId( $id ) {

		$reference = false;

		foreach ( $this->store->getPropertyTables() as $proptable ) {
			if ( ( $reference = $this->findReferenceByPropertyTable( $proptable, $id ) ) !== false ) {
				break;
			}
		}

		if ( !isset( $reference->s_id ) ) {
			$reference = $this->findReferenceByQueryLinksTable( $id );
		}

		// If null is returned it means that a reference was found bu no DI could
		// be matched therefore is categorized as false positive
		if ( isset( $reference->s_id ) ) {
			$reference = $this->store->getObjectIds()->getDataItemById( $reference->s_id );
		}

		return $reference === false || $reference === null ? false : $reference;
	}

	private function findReferenceByPropertyTable( $proptable, $id ) {

		$row = false;

		if ( $proptable->usesIdSubject() ) {
			$row = $this->connection->selectRow(
				$proptable->getName(),
				array( 's_id' ),
				array( 's_id' => $id ),
				__METHOD__
			);
		}

		if ( $row !== false ) {
			return $row;
		}

		$fields = $proptable->getFields( $this->store );

		// Check whether an object reference exists or not
		if ( isset( $fields['o_id'] ) ) {

			// This next time someone ... I'm going to Alaska
			$field = strpos( $proptable->getName(), 'redi' ) ? array( 's_title', 's_namespace' ) : array( 's_id' );

			$row = $this->connection->selectRow(
				$proptable->getName(),
				$field,
				array( 'o_id' => $id ),
				__METHOD__
			);

			if ( $row !== false && strpos( $proptable->getName(), 'redi' ) ) {
				$row->s_id = $this->store->getObjectIds()->findRedirectIdFor( $row->s_title, $row->s_namespace );
			}
		}

		// If the property table is not a fixed table (== assigns a whole
		// table to a specific property with the p_id column being suppressed)
		// then check for the p_id field
		if ( $row === false && !$proptable->isFixedPropertyTable() ) {
			$row = $this->connection->selectRow(
				$proptable->getName(),
				array( 's_id' ),
				array( 'p_id' => $id ),
				__METHOD__
			);
		}

		return $row;
	}

	private function findReferenceByQueryLinksTable( $id ) {

		// If the query table contains a reference then we keep the object (could
		// be a subject, property, or printrequest) where in case the query is
		// removed the object will also loose its reference
		$row = $this->connection->selectRow(
			SQLStore::QUERY_LINKS_TABLE,
			array( 's_id' ),
			array( 'o_id' => $id ),
			__METHOD__
		);

		return $row;
	}

}
