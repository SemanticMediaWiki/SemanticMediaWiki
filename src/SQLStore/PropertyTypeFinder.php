<?php

namespace SMW\SQLStore;

use Exception;
use RuntimeException;
use SMW\DataItems\Property;
use SMW\MediaWiki\Connection\Database;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyTypeFinder {

	/**
	 * @var string
	 */
	private $typeTableName = '';

	/**
	 * @since 2.5
	 */
	public function __construct( private readonly Database $connection ) {
	}

	/**
	 * @since 2.5
	 *
	 * @param string $typeTableName
	 */
	public function setTypeTableName( $typeTableName ): void {
		$this->typeTableName = $typeTableName;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $type
	 *
	 * @return int
	 */
	public function countByType( $type ): int {
		if ( strpos( $type, 'http://semantic-mediawiki.org/swivt/1.0#' ) === false ) {
			$type = 'http://semantic-mediawiki.org/swivt/1.0#' . $type;
		}

		$row = $this->connection->newSelectQueryBuilder()
			->select( [ 'COUNT(*) AS count' ] )
			->from( PropertyTableDefinitionBuilder::makeTableName( '_TYPE' ) )
			->where( [ 'o_serialized' => $type ] )
			->caller( __METHOD__ )
			->fetchRow();

		return isset( $row->count ) ? (int)$row->count : 0;
	}

	/**
	 * @since 2.5
	 *
	 * @param Property $property
	 *
	 * @return string
	 * @throws RuntimeException
	 */
	public function findTypeID( Property $property ) {
		try {
			$row = $this->connection->newSelectQueryBuilder()
				->select( [ 'smw_id' ] )
				->from( SQLStore::ID_TABLE )
				->where( [
					'smw_namespace' => SMW_NS_PROPERTY,
					'smw_title'     => $property->getKey(),
					'smw_iw'        => '',
					'smw_subobject' => '',
				] )
				->caller( __METHOD__ )
				->fetchRow();
		} catch ( Exception ) {
			$row = false;
		}

		if ( !$row || !isset( $row->smw_id ) ) {
			return $GLOBALS['smwgPDefaultType'];
		}

		if ( $this->typeTableName === '' ) {
			throw new RuntimeException( "Missing a table name" );
		}

		// The Finder is executed before tables are initialized with a corresponding
		// and matchable DIHandler therefore using Store::getPropertyValue cannot
		// be used at this point as it would create a circular reference during
		// the table initialization.
		//
		// We expect it to be a URI table with `o_serialized` containing the
		// type string
		$row = $this->connection->newSelectQueryBuilder()
			->select( [ 'o_serialized' ] )
			->from( $this->typeTableName )
			->where( [ 's_id' => $row->smw_id ] )
			->caller( __METHOD__ )
			->fetchRow();

		if ( $row === false ) {
			return $GLOBALS['smwgPDefaultType'];
		}

		// e.g. http://semantic-mediawiki.org/swivt/1.0#_num
		[ $url, $fragment ] = explode( "#", $row->o_serialized );

		return $fragment;
	}

}
