<?php

namespace SMW\Schema;

use SMW\DIProperty;
use SMW\RequestOptions;
use SMW\Store;
use SMWDIBlob as DIBlob;
use Title;
use WikiPage;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SchemaFinder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $type
	 *
	 * @return SchemaList
	 */
	public function getSchemaListByType( $type ) {

		$data = [];
		$list = [];

		$subjects = $this->store->getPropertySubjects(
			new DIProperty( '_SCHEMA_TYPE' ),
			new DIBlob( $type )
		);

		foreach ( $subjects as $subject ) {

			$dataItems = $this->store->getPropertyValues(
				$subject,
				new DIProperty( '_SCHEMA_DEF' )
			);

			if ( $dataItems === [] ) {
				continue;
			}

			$dataItem = end( $dataItems );
			$subject = str_replace( '_', ' ', $subject->getDBKey() );

			$data = json_decode( $dataItem->getString(), true );

			if ( json_last_error() !== JSON_ERROR_NONE ) {
				continue;
			}

			$list[] = new SchemaDefinition( $subject, $data );
		}

		return new SchemaList( $list );
	}

}
