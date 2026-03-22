<?php

namespace SMW\SQLStore\Lookup;

use MediaWiki\Message\Message;
use RuntimeException;
use SMW\DataItems\DataItem;
use SMW\DataItems\Error;
use SMW\DataItems\Property;
use SMW\Exception\PropertyLabelNotResolvedException;
use SMW\RequestOptions;
use SMW\SQLStore\SQLStore;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 2.2
 *
 * @author mwjames
 * @author Nischay Nahata
 */
class UndeclaredPropertyListLookup implements ListLookup {

	/**
	 * @since 2.2
	 */
	public function __construct(
		private readonly Store $store,
		private $defaultPropertyType,
		private readonly ?RequestOptions $requestOptions = null,
	) {
	}

	/**
	 * @since 2.2
	 *
	 * @return Property[]
	 * @throws RuntimeException
	 */
	public function fetchList(): array {
		if ( $this->requestOptions === null ) {
			throw new RuntimeException( "Missing requestOptions" );
		}

		// Wanted Properties must have the default type
		$propertyTable = $this->getPropertyTableForType( $this->defaultPropertyType );

		if ( $propertyTable->isFixedPropertyTable() ) {
			return [];
		}

		return $this->buildPropertyList( $this->selectPropertiesFromTable( $propertyTable ) );
	}

	/**
	 * @since 2.2
	 *
	 * @return bool
	 */
	public function isFromCache(): bool {
		return false;
	}

	/**
	 * @since 2.2
	 *
	 * @return int
	 */
	public function getTimestamp() {
		return wfTimestamp( TS_UNIX );
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getHash(): string {
		return __METHOD__ . '#' . ( $this->requestOptions !== null ? $this->requestOptions->getHash() : '' );
	}

	private function selectPropertiesFromTable( $propertyTable ) {
		$options = $this->store->getSQLOptions( $this->requestOptions, 'title' );
		$idTable = SQLStore::ID_TABLE;

		$options['ORDER BY'] = 'count DESC';

		// Postgres Error: 42803 ERROR: ...smw_title must appear in the GROUP BY
		// clause or be used in an aggregate function
		$options['GROUP BY'] = 'smw_id, smw_title';

		$conditions = [
			'smw_id > ' . SQLStore::FIXED_PROPERTY_ID_UPPERBOUND,
			'smw_namespace' => SMW_NS_PROPERTY,
			'smw_proptable_hash IS NULL',
			'smw_iw' => '',
			'smw_subobject' => ''
		];

		$joinCond = 'p_id';

		foreach ( $this->requestOptions->getExtraConditions() as $extaCondition ) {
			if ( isset( $extaCondition['filter.unapprove'] ) ) {
				$joinCond = 'o_id';
			}
		}

		$res = $this->store->getConnection( 'mw.db' )->select(
			[ $idTable, $propertyTable->getName() ],
			[ 'smw_id', 'smw_title', 'COUNT(*) as count' ],
			$conditions,
			__METHOD__,
			$options,
			[
				$idTable => [
					'INNER JOIN', "$joinCond=smw_id"
				]
			]
		);

		return $res;
	}

	/**
	 * @return array{mixed, mixed}[]
	 */
	private function buildPropertyList( $res ): array {
		$result = [];

		foreach ( $res as $row ) {
			$result[] = [ $this->addPropertyFor( $row->smw_title ), $row->count ];
		}

		return $result;
	}

	private function addPropertyFor( $title ): DataItem {
		try {
			$property = new Property( $title );
		} catch ( PropertyLabelNotResolvedException $e ) {
			$property = new Error( new Message( 'smw_noproperty', [ $title ] ) );
		}

		return $property;
	}

	private function getPropertyTableForType( $type ) {
		$propertyTables = $this->store->getPropertyTables();
		$tableIdForType = $this->store->findTypeTableId( $type );

		if ( isset( $propertyTables[$tableIdForType] ) ) {
			return $propertyTables[$tableIdForType];
		}

		throw new RuntimeException( "Tried to access a table that doesn't exist for {$type}." );
	}

}
