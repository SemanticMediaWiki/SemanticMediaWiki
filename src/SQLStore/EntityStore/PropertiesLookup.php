<?php

namespace SMW\SQLStore\EntityStore;

use SMW\SQLStore\SQLStore;
use SMW\SQLStore\PropertyTableDefinition as TableDefinition;
use SMWDataItem as DataItem;
use SMW\DIWikiPage;
use SMW\RequestOptions;
use RuntimeException;

/**
 * @license GNU GPL v2
 * @since 3.0
 *
 * @author mwjames
 */
class PropertiesLookup {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @since 3.0
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.0
	 *
	 * @return RequestOptions|null
	 */
	public function newRequestOptions( RequestOptions $requestOptions = null ) {

		if ( $requestOptions !== null ) {
			$clone = clone $requestOptions;
			$clone->limit = $requestOptions->limit + $requestOptions->offset;
			$clone->offset = 0;
		} else {
			$clone = null;
		}

		return $clone;
	}

	/**
	 * @see Store::getProperties
	 *
	 * @since 3.0
	 *
	 * {@inheritDoc}
	 */
	public function fetchFromTable( DIWikiPage $subject, TableDefinition $propertyTable, RequestOptions $requestOptions = null ) {

		$connection = $this->store->getConnection( 'mw.db' );
		$query = $connection->newQuery();

		$query->type( 'SELECT' );
		$query->table( $propertyTable->getName() );

		if ( $propertyTable->usesIdSubject() ) {
			$query->condition( $query->eq( 's_id', $subject->getId() ) );
		} elseif ( $subject->getInterwiki() === '' ) {
			$query->condition( $query->eq( 's_title', $subject->getDBkey() ) );
			$query->condition( $query->eq( 's_namespace', $subject->getNamespace() ) );
		} else {
			// subjects with non-empty interwiki cannot have properties
			return [];
		}

		if ( $propertyTable->isFixedPropertyTable() ) {
			return $this->fetchFromFixedTable( $query, $propertyTable->getFixedProperty() );
		}

		$query->join(
			'INNER JOIN',
			[ SQLStore::ID_TABLE => "ON smw_id=p_id" ]
		);

		$query->fields( [ 'smw_title', 'smw_sortkey' ] );

		// (select sortkey since it might be used in ordering (needed by Postgres))
		$query->condition( $this->store->getSQLConditions(
			$requestOptions,
			'smw_sortkey',
			'smw_sortkey'
		) );

		$opt =  $this->store->getSQLOptions(
			$requestOptions,
			'smw_sortkey'
		);

		$query->options( $opt + [ 'DISTINCT' => true ] );

		return $query->execute( __METHOD__ );
	}

	private function fetchFromFixedTable( $query, $title ) {

		// just check if subject occurs in table
		$query->options(
			[ 'LIMIT' => 1 ]
		);

		$query->field( '*' );
		$res = $query->execute( __METHOD__ );

		if ( $res->numRows() > 0 ) {
			return [ $title ];
		}

		return [];
	}

}
