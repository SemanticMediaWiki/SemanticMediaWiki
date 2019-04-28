<?php

namespace SMW\SQLStore\Lookup;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Store;
use SMW\SQLStore\SQLStore;
use SMWDataItem as DataItem;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ErrorLookup {

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
	 * @param string $errorType
	 * @param DIWikiPage $subject = null
	 *
	 * @return Iterator/array
	 */
	public function findErrorsByType( $errorType, DIWikiPage $subject = null ) {
		return $this->fetchFromTable( $errorType, $subject );
	}

	private function fetchFromTable( $errorType, $subject ) {

		/**
		 * SELECT t2.s_id AS s_id, t3.o_hash AS o_hash, t3.o_blob AS o_blob
		 *  FROM `smw_object_ids` AS t0
		 *  INNER JOIN `smw_di_wikipage` AS t1 ON t0.smw_id=t1.s_id
		 *  INNER JOIN `smw_di_blob` AS t2 ON t1.o_id=t2.s_id
		 *  INNER JOIN `smw_di_blob` AS t3 ON t3.s_id=t2.s_id
		 *  WHERE
		 *   (t0.smw_iw!=':smw') AND
		 *   (t0.smw_iw!=':smw-delete') AND
		 *   (t1.s_id='310187') AND
		 *   (t1.p_id='310180') AND
		 *   (t2.p_id='363592') AND (t2.o_hash='constraint') AND
		 *   (t3.p_id='310183')
		 */

		$connection = $this->store->getConnection( 'mw.db' );
		$idTable = $this->store->getObjectIds();

		$pid = $idTable->getSMWPropertyID(
			new DIProperty( '_ERRC' )
		);

		$connection = $this->store->getConnection( 'mw.db' );
		$query = $connection->newQuery();

		$wpg_table = $this->store->findDiTypeTableId(
			DataItem::TYPE_WIKIPAGE
		);

		$query->type( 'SELECT' );

		$query->table( SQLStore::ID_TABLE, 't0' );
		$query->condition( $query->neq( "t0.smw_iw", SMW_SQL3_SMWIW_OUTDATED ) );
		$query->condition( $query->neq( "t0.smw_iw", SMW_SQL3_SMWDELETEIW ) );

		$query->join(
			'INNER JOIN',
			[ $wpg_table => 't1 ON t0.smw_id=t1.s_id' ]
		);

		if ( $subject !== null ) {
			$sid = $idTable->getId(
				$subject
			);

			$query->condition( $query->eq( "t1.s_id", $sid ) );
		}

		$query->condition( $query->eq( "t1.p_id", $pid ) );

		$property = new DIProperty( '_ERR_TYPE' );

		$pid = $idTable->getSMWPropertyID(
			$property
		);

		$err_type_table = $this->store->findPropertyTableID(
			$property
		);

		$query->join(
			'INNER JOIN',
			[ $err_type_table => 't2 ON t1.o_id=t2.s_id' ]
		);

		$query->condition( $query->eq( "t2.p_id", $pid ) );

		if ( $errorType !== null ) {
			$query->condition( $query->eq( "t2.o_hash", $errorType ) );
		}

		$property = new DIProperty( '_ERRT' );

		$pid = $idTable->getSMWPropertyID(
			$property
		);

		$errt_table = $this->store->findPropertyTableID(
			$property
		);

		$query->join(
			'INNER JOIN',
			[ $errt_table => 't3 ON t3.s_id=t2.s_id' ]
		);

		$query->condition( $query->eq( "t3.p_id", $pid ) );

		$query->field( 't2.s_id', 's_id' );
		$query->field( 't3.o_hash', 'o_hash' );
		$query->field( 't3.o_blob', 'o_blob' );

		return $query->execute( __METHOD__ );
	}

}
