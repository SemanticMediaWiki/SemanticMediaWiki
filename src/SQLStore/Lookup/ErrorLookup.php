<?php

namespace SMW\SQLStore\Lookup;

use SMW\DataItems\DataItem;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\RequestOptions;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use Traversable;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class ErrorLookup {

	/**
	 * @since 3.1
	 */
	public function __construct( private readonly Store $store ) {
	}

	/**
	 * @since 3.1
	 *
	 * @param Traversable $res
	 *
	 * @return array
	 */
	public function buildArray( /* iterable */ $res ): array {
		$connection = $this->store->getConnection( 'mw.db' );
		$messages = [];

		foreach ( $res as $row ) {

			if ( $row->o_blob !== null ) {
				$msg = $connection->unescape_bytea( $row->o_blob );
			} else {
				$msg = $row->o_hash;
			}

			$messages[] = $msg;
		}

		return $messages;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $errorType
	 * @param WikiPage|null $subject = null
	 * @param ?RequestOptions $requestOptions
	 *
	 * @return iterable
	 */
	public function findErrorsByType( $errorType, ?WikiPage $subject = null, ?RequestOptions $requestOptions = null ) {
		if ( $requestOptions === null ) {
			$requestOptions = new RequestOptions();
		}

		return $this->fetchFromTable( $errorType, $subject, $requestOptions );
	}

	/**
	 * @param string $errorType
	 * @param WikiPage|null $subject
	 * @param RequestOptions $requestOptions
	 *
	 * @return iterable
	 */
	private function fetchFromTable( $errorType, ?WikiPage $subject, RequestOptions $requestOptions ) {
		$checkConstraintErrors = $requestOptions->getOption( 'checkConstraintErrors' );

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

		$idTable->warmUpCache(
			[
				$subject,
				new Property( '_ERR_TYPE' ),
				new Property( '_ERRT' ),
				new Property( '_ERRC' ),
				new Property( '_SOBJ' )
			]
		);

		$pid = $idTable->getSMWPropertyID(
			new Property( '_ERRC' )
		);

		$connection = $this->store->getConnection( 'mw.db' );

		$wpg_table = $this->store->findDiTypeTableId(
			DataItem::TYPE_WIKIPAGE
		);

		$sobj_type_table = $this->store->findPropertyTableID(
			new Property( '_SOBJ' )
		);

		$qb = $connection->newSelectQueryBuilder()
			->from( SQLStore::ID_TABLE, 't0' )
			->where( $connection->expr( 't0.smw_iw', '!=', SMW_SQL3_SMWIW_OUTDATED ) )
			->andWhere( $connection->expr( 't0.smw_iw', '!=', SMW_SQL3_SMWDELETEIW ) )
			->join( $wpg_table, 't1', 't0.smw_id=t1.s_id' );

		if ( $subject !== null ) {

			$sid = $idTable->getId(
				$subject
			);

			if ( $checkConstraintErrors === SMW_CONSTRAINT_ERR_CHECK_ALL ) {
				$qb->leftJoin( $sobj_type_table, 's1', 's1.o_id=t1.s_id' );

				$qb->andWhere(
					'(s1.s_id=' . $connection->addQuotes( $sid ) .
					' OR t1.s_id=' . $connection->addQuotes( $sid ) . ')'
				);
			} else {
				$qb->andWhere( [ 't1.s_id' => $sid ] );
			}
		}

		$qb->andWhere( [ 't1.p_id' => $pid ] );

		$property = new Property( '_ERR_TYPE' );

		$pid = $idTable->getSMWPropertyID(
			$property
		);

		$err_type_table = $this->store->findPropertyTableID(
			$property
		);

		$qb->join( $err_type_table, 't2', 't1.o_id=t2.s_id' );

		$qb->andWhere( [ 't2.p_id' => $pid ] );

		if ( $errorType !== null ) {
			$qb->andWhere( [ 't2.o_hash' => $errorType ] );
		}

		$property = new Property( '_ERRT' );

		$pid = $idTable->getSMWPropertyID(
			$property
		);

		$errt_table = $this->store->findPropertyTableID(
			$property
		);

		$qb->join( $errt_table, 't3', 't3.s_id=t2.s_id' );

		$qb->andWhere( [ 't3.p_id' => $pid ] );

		$qb->select( [ 's_id' => 't2.s_id', 'o_hash' => 't3.o_hash', 'o_blob' => 't3.o_blob' ] );

		if ( $requestOptions->getLimit() > 0 ) {
			$qb->limit( $requestOptions->getLimit() );
		}

		$caller = __METHOD__;

		if ( strval( $requestOptions->getCaller() ) !== '' ) {
			$caller .= " (for " . $requestOptions->getCaller() . ")";
		}

		return $qb->caller( $caller )->fetchResultSet();
	}

}
