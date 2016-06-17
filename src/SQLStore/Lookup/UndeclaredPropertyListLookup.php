<?php

namespace SMW\SQLStore\Lookup;

use RuntimeException;
use SMW\DIProperty;
use SMW\InvalidPropertyException;
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
			return array();
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
		$idTable = \SMWSQLStore3::ID_TABLE;

		$options['ORDER BY'] = 'count DESC';
		$options['GROUP BY'] = 'smw_title';

		$conditions = array(
			'smw_id > ' . SQLStore::FIXED_PROPERTY_ID_UPPERBOUND,
			'page_id IS NULL',
			'smw_iw' => '',
			'smw_subobject' => ''
		);

		$db = $this->store->getConnection( 'mw.db' );

		$res = $db->select(
			array( $idTable, 'page', $propertyTable->getName() ),
			array( 'smw_title', 'COUNT(*) as count' ),
			$conditions,
			__METHOD__,
			$options,
			array(
				$idTable => array(
					'INNER JOIN', 'p_id=smw_id'
				),
				'page' => array(
					'LEFT JOIN', array( 'page_namespace=' . $db->addQuotes( SMW_NS_PROPERTY ), 'page_title=smw_title'  )
				)
			)
		);

		return $res;
	}

	private function buildPropertyList( $res ) {

		$result = array();

		foreach ( $res as $row ) {
			$result[] = array( $this->addPropertyFor( $row->smw_title ), $row->count );
		}

		return $result;
	}

	private function addPropertyFor( $title ) {

		try {
			$property = new DIProperty( $title );
		} catch ( InvalidPropertyException $e ) {
			$property = new DIError( new \Message( 'smw_noproperty', array( $title ) ) );
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
