<?php

namespace SMW\SQLStore\Lookup;

use SMW\Store;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SQLStore\SQLStore;
use SMW\Message;
use SMW\DataValueFactory;
use SMWContainerSemanticData as ContainerSemanticData;
use SMWDIBlob as DIBlob;
use SMWDIContainer as DIContainer;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class MonolingualTextLookup {

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
	 * @param DIWikiPage $subject
	 *
	 * @return []
	 */
	public function newDataValue( DIWikiPage $subject, DIProperty $property, $languageCode = null ) {

		$res = $this->fetchFromTable( $subject, $property, $languageCode );
		$dataValue = null;

		$connection = $this->store->getConnection( 'mw.db' );

		foreach ( $res as $row ) {

			$containerSemanticData = $this->newContainerSemanticData(
				$row
			);

			$text = $row->text_long === null ? $row->text_short : $row->text_long;

			if ( $row->text_long !== null && $connection->isType( 'postgres' ) ) {
				$text = pg_unescape_bytea( $text );
			}

			$containerSemanticData->addPropertyObjectValue(
				new DIProperty( '_TEXT' ),
				new DIBlob( $text )
			);

			$containerSemanticData->addPropertyObjectValue(
				new DIProperty( '_LCODE' ),
				new DIBlob( $row->lcode )
			);

			$dataValue = DataValueFactory::getInstance()->newDataValueByItem(
				$subject,
				$property
			);

			$dataValue->setDataItem(
				new DIContainer( $containerSemanticData )
			);
		}

		return $dataValue;
	}

	/**
	 * @since 3.1
	 *
	 * @param DIWikiPage $subject
	 *
	 * @return []
	 */
	public function fetchFromTable( DIWikiPage $subject, DIProperty $property, $languageCode = null ) {

		/**
		 * This method avoids access to `Store::getSemanticData` in order to
		 * optimize the query and produce something like:
		 *
		 */

		$sid = $this->store->getObjectIds()->getSMWPageID(
			$subject->getDBKey(),
			$subject->getNamespace(),
			$subject->getInterWiki(),
			$subject->getSubobjectName()
		);

		$connection = $this->store->getConnection( 'mw.db' );
		$query = $connection->newQuery();

		$query->type( 'SELECT' );

		$propTable = $this->getPropertyTable(
			$property
		);

		$query->table( $propTable->getName(), 't0' );
		$query->condition( $query->eq( "t0.s_id", $sid ) );

		if ( !$propTable->isFixedPropertyTable() ) {

			$pid = $this->store->getObjectIds()->getSMWPropertyID(
				$property
			);

			$query->condition( $query->eq( "t0.p_id", $pid ) );

			$query->join(
				'INNER JOIN',
				[ SQLStore::ID_TABLE => 't1 ON t0.p_id=t1.smw_id' ]
			);
		}

		$query->condition( $query->neq( "o0.smw_iw", SMW_SQL3_SMWIW_OUTDATED ) );
		$query->condition( $query->neq( "o0.smw_iw", SMW_SQL3_SMWDELETEIW ) );

		$query->join(
			'INNER JOIN',
			[ SQLStore::ID_TABLE => 'o0 ON t0.o_id=o0.smw_id' ]
		);

		$text = new DIProperty( '_TEXT' );

		$text_table = $this->getPropertyTable(
			$text
		);

		$query->join(
			'INNER JOIN',
			[ $text_table->getName() => 't2 ON t2.s_id=o0.smw_id' ]
		);

		$lcode = new DIProperty( '_LCODE' );

		$lcode_table = $this->getPropertyTable(
			$lcode
		);

		$query->join(
			'INNER JOIN',
			[ $lcode_table->getName() => 't3 ON t3.s_id=o0.smw_id' ]
		);

		if ( $languageCode !== null ) {
			$query->condition( $query->eq( "t3.o_hash", $languageCode ) );
		}

		$query->field( 't0.o_id', 'id' );
		$query->field( 'o0.smw_title', 'v0' );
		$query->field( 'o0.smw_namespace', 'v1' );
		$query->field( 'o0.smw_iw', 'v2' );
		$query->field( 'o0.smw_subobject', 'v3' );
		$query->field( 't2.o_hash', 'text_short' );
		$query->field( 't2.o_blob', 'text_long' );
		$query->field( 't3.o_hash', 'lcode' );

		return $query->execute( __METHOD__ );
	}

	private function getPropertyTable( DIProperty $property ) {

		$propTableId = $this->store->findPropertyTableID(
			$property
		);

		$propTables = $this->store->getPropertyTables();

		if ( !isset( $propTables[$propTableId] ) ) {
			return [];
		}

		return $propTables[$propTableId];
	}

	private function newContainerSemanticData( $row ) {

		$subject = new DIWikiPage(
			$row->v0,
			$row->v1,
			$row->v2,
			$row->v3
		);

		return new ContainerSemanticData( $subject );
	}

}
