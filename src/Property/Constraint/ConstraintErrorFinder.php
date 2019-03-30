<?php

namespace SMW\Property\Constraint;

use SMW\Store;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SQLStore\SQLStore;
use SMW\Message;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintErrorFinder {

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
	public function findConstraintErrors( DIWikiPage $subject ) {

		/**
		 * This method avoids access to `Store::getSemanticData` in order to
		 * optimize the query and produce something like:
		 *
		 * SELECT t0.o_id AS v0, t4.o_hash AS v2, t4.o_blob AS v3
		 * FROM
		 *  `smw_di_wikipage` AS t0
		 * INNER JOIN
		 *  `smw_object_ids` AS p ON p_id=t1.smw_id
		 * INNER JOIN
		 *  `smw_object_ids` AS t2 ON o_id=t2.smw_id
		 * INNER JOIN
		 *  `smw_di_blob` AS t3 ON t3.s_id=t2.smw_id
		 * INNER JOIN
		 *  `smw_di_blob` AS t4 ON t4.s_id=t2.smw_id
		 * WHERE
		 *  (t0.s_id='660') AND (t0.p_id='511') AND
		 *  (t1.smw_iw!=':smw') AND (t1.smw_iw!=':smw-delete') AND
		 *  (t3.p_id='3354') AND (t3.o_hash='constraint') AND
		 *  (t4.p_id='515')
		 */

		$id = $this->store->getObjectIds()->getId(
			$subject
		);

		$connection = $this->store->getConnection( 'mw.db' );
		$query = $connection->newQuery();

		$query->type( 'SELECT' );

		// Find subobjects that match property `_ERRC` and the subject
		$property = new DIProperty( '_ERRC' );

		$propTable = $this->getPropertyTable(
			$property
		);

		$errc_pid = $this->store->getObjectIds()->getSMWPropertyID(
			$property
		);

		$query->table( $propTable->getName(), 't0' );
		$query->condition( $query->eq( "t0.s_id", $id ) );
		$query->condition( $query->eq( "t0.p_id", $errc_pid ) );

		$query->join(
			'INNER JOIN',
			[ SQLStore::ID_TABLE => 't1 ON p_id=t1.smw_id' ]
		);

		$query->condition( $query->neq( "t1.smw_iw", SMW_SQL3_SMWIW_OUTDATED ) );
		$query->condition( $query->neq( "t1.smw_iw", SMW_SQL3_SMWDELETEIW ) );

		$query->join(
			'INNER JOIN',
			[ SQLStore::ID_TABLE => 't2 ON o_id=t2.smw_id' ]
		);

		// Match only those errors with type `constraint`
		$property = new DIProperty( '_ERR_TYPE' );

		$err_type_pid = $this->store->getObjectIds()->getSMWPropertyID(
			$property
		);

		$err_type_table = $this->getPropertyTable(
			$property
		);

		$query->condition( $query->eq( "t3.p_id", $err_type_pid ) );
		$query->condition( $query->eq( "t3.o_hash", ConstraintError::ERROR_TYPE ) );

		$query->join(
			'INNER JOIN',
			[ $err_type_table->getName() => 't3 ON t3.s_id=t2.smw_id' ]
		);

		// Fetch the text for those `constraint` type matches
		$property = new DIProperty( '_ERRT' );

		$err_pid = $this->store->getObjectIds()->getSMWPropertyID(
			$property
		);

		$err_table = $this->getPropertyTable(
			$property
		);

		$query->condition( $query->eq( "t4.p_id", $err_pid ) );

		$query->join(
			'INNER JOIN',
			[ $err_table->getName() => 't4 ON t4.s_id=t2.smw_id' ]
		);

		$query->field( 't0.o_id', 'v0' );
		$query->field( 't4.o_hash', 'v2' );
		$query->field( 't4.o_blob', 'v3' );

		$res = $query->execute( __METHOD__ );
		$errors = [];

		foreach ( $res as $row ) {
			if ( $row->v3 === null ) {
				$errors[] = Message::decode( $row->v2 );
			} else {
				$errors[] = Message::decode( $row->v3 );
			}
		}

		return $errors;
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

}
