<?php

namespace SMW\SQLStore\EntityStore;

use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Connection\LegacyOptionsApplier;
use SMW\RequestOptions;
use SMW\SQLStore\PropertyTableDefinition as TableDefinition;
use SMW\SQLStore\SQLStore;
use Wikimedia\Rdbms\SelectQueryBuilder;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class PropertiesLookup {

	/**
	 * @since 3.0
	 */
	public function __construct( private readonly SQLStore $store ) {
	}

	/**
	 * @since 3.0
	 *
	 * @return RequestOptions|null
	 */
	public function newRequestOptions( ?RequestOptions $requestOptions = null ): ?RequestOptions {
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
	public function fetchFromTable( WikiPage $subject, TableDefinition $propertyTable, ?RequestOptions $requestOptions = null ) {
		$connection = $this->store->getConnection( 'mw.db' );

		$qb = $connection->newSelectQueryBuilder()
			->from( $propertyTable->getName() );

		if ( $propertyTable->usesIdSubject() ) {
			$qb->where( [ 's_id' => $subject->getId() ] );
		} elseif ( $subject->getInterwiki() === '' ) {
			$qb->where( [
				's_title' => $subject->getDBkey(),
				's_namespace' => $subject->getNamespace(),
			] );
		} else {
			// subjects with non-empty interwiki cannot have properties
			return [];
		}

		if ( $propertyTable->isFixedPropertyTable() ) {
			return $this->fetchFromFixedTable( $qb, $propertyTable->getFixedProperty() );
		}

		$qb->join( SQLStore::ID_TABLE, null, 'smw_id=p_id' )
			->select( [ 'smw_title', 'smw_sortkey' ] );

		// (select sortkey since it might be used in ordering (needed by Postgres))
		$sqlConds = $this->store->getSQLConditions(
			$requestOptions,
			'smw_sortkey',
			'smw_sortkey'
		);

		if ( $sqlConds !== '' ) {
			$qb->andWhere( $sqlConds );
		}

		$qb->distinct();

		LegacyOptionsApplier::applyTo(
			$qb,
			$this->store->getSQLOptions( $requestOptions, 'smw_sortkey' )
		);

		return $qb->caller( __METHOD__ )->fetchResultSet();
	}

	private function fetchFromFixedTable( SelectQueryBuilder $qb, string $title ): array {
		// just check if subject occurs in table
		$res = $qb->select( '*' )
			->limit( 1 )
			->caller( __METHOD__ )
			->fetchResultSet();

		if ( $res->numRows() > 0 ) {
			return [ $title ];
		}

		return [];
	}

}
