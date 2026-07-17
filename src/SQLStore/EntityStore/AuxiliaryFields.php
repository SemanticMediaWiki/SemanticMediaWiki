<?php

namespace SMW\SQLStore\EntityStore;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use SMW\DataItems\WikiPage;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\SQLStore;
use SMW\Utils\HmacSerializer;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class AuxiliaryFields {

	use LoggerAwareTrait;

	const COUNTMAP_CACHE_ID = 'count.map';

	/**
	 * @since 3.2
	 */
	public function __construct(
		private readonly Database $connection,
		private readonly IdCacheManager $idCacheManager,
	) {
		$this->logger = new NullLogger();
	}

	/**
	 * @since 3.2
	 *
	 * @param WikiPage[] $subjects
	 *
	 * @return FieldList
	 */
	public function prefetchFieldList( array $subjects ): FieldList {
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

			// An empty map is stored as SQL NULL, which unescape_bytea() returns
			// as null on MySQL but as '' on PostgreSQL; treat both as empty so a
			// normal empty map is not run through uncompress() below.
			if ( $countmap !== null && $countmap !== '' ) {
				$map = HmacSerializer::uncompress( $countmap );

				// uncompress() returns false when the stored blob cannot be
				// deserialized (for example when it was written under a
				// different $wgSecretKey); fall back to an empty map so the
				// FieldList consumers iterate an array rather than a bool.
				if ( !is_array( $map ) ) {
					$this->logger->warning(
						'Count map for entity {id} could not be deserialized and was treated as empty; this can happen after $wgSecretKey changed.',
						[ 'id' => $row->smw_id ]
					);
					$map = [];
				}
			}

			$cache->save( (string)$row->smw_id, $map );
			$countMaps[$hashMap[$row->smw_hash]] = [ $row->smw_id => $map ];
		}

		return new FieldList( $countMaps );
	}

	/**
	 * @since 3.2
	 *
	 * @param int $sid
	 * @param array|null $seqmap
	 * @param array|null $countmap
	 */
	public function setFieldMaps( $sid, ?array $seqmap = null, ?array $countmap = null ): void {
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

		$this->connection->newInsertQueryBuilder()
			->insertInto( SQLStore::ID_AUXILIARY_TABLE )
			->row( [
				'smw_id' => $sid,
				'smw_seqmap' => $seqmap,
				'smw_countmap' => $countmap
			] )
			->onDuplicateKeyUpdate()
			->uniqueIndexFields( [ 'smw_id' ] )
			->set( [
				'smw_seqmap' => $seqmap,
				'smw_countmap' => $countmap
			] )
			->caller( __METHOD__ )
			->execute();

		$cache->save( (string)$sid, $countmap );
	}

	private function fetchCountMap( array $hashes ) {
		return $this->connection->newSelectQueryBuilder()
			->select( [ 't.smw_id', 't.smw_hash', 'p.smw_countmap' ] )
			->from( SQLStore::ID_TABLE, 't' )
			->join( SQLStore::ID_AUXILIARY_TABLE, 'p', 'p.smw_id=t.smw_id' )
			->where( [ 't.smw_hash' => $hashes ] )
			->caller( __METHOD__ )
			->fetchResultSet();
	}

}
