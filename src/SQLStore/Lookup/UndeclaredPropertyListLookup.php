<?php

namespace SMW\SQLStore\Lookup;

use RuntimeException;
use SMW\DIProperty;
use SMW\Exception\PropertyLabelNotResolvedException;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMWDIError as DIError;
use SMWRequestOptions as RequestOptions;

/**
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 * @author Nischay Nahata
 */
class UndeclaredPropertyListLookup implements ListLookup {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var string
	 */
	private $defaultPropertyType;

	/**
	 * @var RequestOptions
	 */
	private $requestOptions;

	/**
	 * @since 2.2
	 *
	 * @param Store $store
	 * @param string $defaultPropertyType
	 * @param RequestOptions $requestOptions|null
	 */
	public function __construct( Store $store, $defaultPropertyType, RequestOptions $requestOptions = null ) {
		$this->store = $store;
		$this->defaultPropertyType = $defaultPropertyType;
		$this->requestOptions = $requestOptions;
	}

	/**
	 * @since 2.2
	 *
	 * @return DIProperty[]
	 * @throws RuntimeException
	 */
	public function fetchList() {

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
	 * @return boolean
	 */
	public function isFromCache() {
		return false;
	}

	/**
	 * @since 2.2
	 *
	 * @return integer
	 */
	public function getTimestamp() {
		return wfTimestamp( TS_UNIX );
	}

	/**
	 * @since 2.2
	 *
	 * @return string
	 */
	public function getHash() {
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

	private function buildPropertyList( $res ) {

		$result = [];

		foreach ( $res as $row ) {
			$result[] = [ $this->addPropertyFor( $row->smw_title ), $row->count ];
		}

		return $result;
	}

	private function addPropertyFor( $title ) {

		try {
			$property = new DIProperty( $title );
		} catch ( PropertyLabelNotResolvedException $e ) {
			$property = new DIError( new \Message( 'smw_noproperty', [ $title ] ) );
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
