<?php

namespace SMW\SQLStore\EntityStore;

use SMW\MediaWiki\Database;
use SMW\Utils\HmacSerializer;
use SMW\SQLStore\SQLStore;
use RuntimeException;

/**
 * @private
 *
 * @license GNU GPL v2
 * @since 3.2
 *
 * @author mwjames
 */
class AuxiliaryFields {

	const COUNTMAP_CACHE_ID = 'count.map';

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var IdCacheManager
	 */
	private $idCacheManager;

	/**
	 * @since 3.2
	 *
	 * @param Database $connection
	 * @param IdCacheManager $idCacheManager
	 */
	public function __construct( Database $connection, IdCacheManager $idCacheManager ) {
		$this->connection = $connection;
		$this->idCacheManager = $idCacheManager;
	}

	/**
	 * @since 3.2
	 *
	 * @param DIWikiPage[] $subjects
	 *
	 * @return FieldList
	 */
	public function prefetchFieldList( array $subjects ) : FieldList {

		if ( $subjects === [] ) {
			return new FieldList( [] );
		}

		$countMaps = [];
		$hashMap = [];

		foreach ( $subjects as $subject ) {
			$hashMap[$subject->getSha1()] = $subject->getHash();
		}

		$cache = $this->idCacheManager->get( self::COUNTMAP_CACHE_ID );

		$rows = $this->fetchCountMap(
			array_keys( $hashMap )
		);

		foreach ( $rows as $row ) {
			$map = [];
			$countmap = $this->connection->unescape_bytea( $row->smw_countmap );

			if ( $countmap !== null ) {
				$map = HmacSerializer::uncompress( $countmap );
			}

			$cache->save( $row->smw_id, $map );
			$countMaps[$hashMap[$row->smw_hash]] = [ $row->smw_id => $map ];
		}

		return new FieldList( $countMaps );
	}

	/**
	 * @since 3.2
	 *
	 * @param integer $sid
	 * @param array|null $seqmap
	 * @param array|null $countmap
	 */
	public function setFieldMaps( $sid, array $seqmap = null, array $countmap = null ) {

		$cache = $this->idCacheManager->get( self::COUNTMAP_CACHE_ID );

		if ( $seqmap !== [] ) {
			$seqmap = $this->connection->escape_bytea(
				HmacSerializer::compress( $seqmap )
			);
		} else {
			$seqmap = null;
		}

		if ( $countmap !== [] ) {
			$countmap = $this->connection->escape_bytea(
				HmacSerializer::compress( $countmap )
			);
		} else {
			$countmap = null;
		}

		$rows = [
			'smw_id' => $sid,
			'smw_seqmap' => $seqmap,
			'smw_countmap' => $countmap
		];

		$this->connection->upsert(
			SQLStore::ID_AUXILIARY_TABLE,
			$rows,
			[
				'smw_id'
			],
			$rows,
			__METHOD__
		);

		$cache->save( $sid, $countmap );
	}

	private function fetchCountMap( array $hashes ) {

		return $this->connection->select(
			[
				// tableName conversion required by SQlite otherwise the
				// integration tests fail
				't' => $this->connection->tableName( SQLStore::ID_TABLE ),
				'p' => $this->connection->tableName( SQLStore::ID_AUXILIARY_TABLE ) ],
			[
				't.smw_id',
				't.smw_hash',
				'p.smw_countmap',
			],
			[
				't.smw_hash' => $hashes
			],
			__METHOD__,
			[],
			[
				'p' => [ 'INNER JOIN', [ 'p.smw_id=t.smw_id' ] ],
			]
		);
	}

}
