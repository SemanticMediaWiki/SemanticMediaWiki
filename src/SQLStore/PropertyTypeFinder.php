<?php

namespace SMW\SQLStore;

use RuntimeException;
use SMW\DIProperty;
use SMW\MediaWiki\Database;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyTypeFinder {

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var string
	 */
	private $typeTableName = '';

	/**
	* @since 2.5
	*
	* @param Database $connection
	*/
	public function __construct( Database $connection ) {
		$this->connection = $connection;
	}

	/**
	* @since 2.5
	*
	* @param string $typeTableName
	*/
	public function setTypeTableName( $typeTableName ) {
		$this->typeTableName = $typeTableName;
	}

	/**
	* @since 3.1
	*
	* @param string $type
	*
	* @return integer
	*/
	public function countByType( $type ) {

		if ( strpos( 'http://semantic-mediawiki.org/swivt/1.0#', $type ) === false ) {
			$type = 'http://semantic-mediawiki.org/swivt/1.0#' . $type;
		}

		$row = $this->connection->selectRow(
			PropertyTableDefinitionBuilder::makeTableName( '_TYPE' ),
			'COUNT(*) AS count',
			[
				'o_serialized' => $type
			],
			__METHOD__
		);

		return isset( $row->count ) ? (int)$row->count : 0;
	}

	/**
	* @since 2.5
	*
	* @param DIProperty $property
	*
	* @return string
	* @throws RuntimeException
	*/
	public function findTypeID( DIProperty $property ) {

		try {
			$row = $this->connection->selectRow(
				SQLStore::ID_TABLE,
				[
					'smw_id'
				],
				[
					'smw_namespace' => SMW_NS_PROPERTY,
					'smw_title' => $property->getKey(),
					'smw_iw' => '',
					'smw_subobject' => ''
				],
				__METHOD__
			);
		} catch ( \Exception $e ) {
			$row = false;
		}

		if ( !isset( $row->smw_id ) ) {
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
		$row = $this->connection->selectRow(
			$this->typeTableName,
			[
				'o_serialized'
			],
			[
				's_id' => $row->smw_id
			],
			__METHOD__
		);

		if ( $row === false ) {
			return $GLOBALS['smwgPDefaultType'];
		}

		// e.g. http://semantic-mediawiki.org/swivt/1.0#_num
		list( $url, $fragment ) = explode( "#", $row->o_serialized );

		return $fragment;
	}

}
