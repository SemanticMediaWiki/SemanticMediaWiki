<?php

namespace SMW\SQLStore\EntityStore;

use SMW\MediaWiki\Database;
use SMW\Utils\HmacSerializer;
use SMW\SQLStore\SQLStore;

/**
 * @private
 *
 * @license GNU GPL v2
 * @since 3.1
 *
 * @author mwjames
 */
class SequenceMapFinder {

	/**
	 * @var Database
	 */
	private $connection;

	/**
	 * @var IdCacheManager
	 */
	private $idCacheManager;

	/**
	 * @var []
	 */
	private $preloaded = [];

	/**
	 * @since 3.1
	 *
	 * @param Database $connection
	 * @param IdCacheManager $idCacheManager
	 */
	public function __construct( Database $connection, IdCacheManager $idCacheManager ) {
		$this->connection = $connection;
		$this->idCacheManager = $idCacheManager;
	}

	/**
	 * Update the sequence.map (smw_seqmap) for a given entity ID
	 *
	 * @since 3.1
	 *
	 * @param integer $sid
	 * @param array $map
	 */
	public function setMap( $sid, array $map = null ) {

		if ( $map === null ) {
			return;
		}

		if ( $map !== [] ) {
			$map = $this->connection->escape_bytea( HmacSerializer::compress( $map ) );
		} else {
			$map = null;
		}

		$rows = [
			'smw_id' => $sid,
			'smw_seqmap' => $map
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
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $sid
	 *
	 * @return array
	 */
	public function findMapById( $sid ) {

		$omap = [];
		$cache = $this->idCacheManager->get( 'sequence.map' );

		if ( ( $map = $cache->fetch( $sid ) ) !== false ) {
			return $map;
		}

		$row = $this->connection->selectRow(
			SQLStore::ID_AUXILIARY_TABLE,
			[
				'smw_seqmap'
			],
			[
				'smw_id' => $sid
			],
			__METHOD__
		);

		if ( $row !== false ) {
			$omap = $row->smw_seqmap;
		}

		if ( $omap !== null && is_string( $omap ) ) {
			$omap = $this->connection->unescape_bytea( $omap );
		}

		if ( $omap === [] || $omap === null ) {
			$map = [];
		} else {
			$map = HmacSerializer::uncompress( $omap );
		}

		$cache->save( $sid, $map );

		return $map;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $ids
	 */
	public function prefetchSequenceMap( array $ids ) {

		sort( $ids );
		$hash = md5( json_encode( $ids ) );

		if ( isset( $this->preloaded[$hash] ) ) {
			return;
		}

		$cache = $this->idCacheManager->get( 'sequence.map' );

		$rows = $this->connection->select(
			SQLStore::ID_AUXILIARY_TABLE,
			[
				'smw_id',
				'smw_seqmap'
			],
			[
				'smw_id' => $ids
			],
			__METHOD__
		);

		$inverted_ids = array_flip( $ids );

		foreach ( $rows as $row ) {
			$id = (int)$row->smw_id;
			$omap = $row->smw_seqmap;

			if ( $omap !== null && is_string( $omap ) ) {
				$omap = $this->connection->unescape_bytea( $omap );
			}

			if ( $omap !== null && $omap !== false ) {
				$map = HmacSerializer::uncompress( $omap );
			} else {
				$map = [];
			}

			// Removes those that found a matching pair
			unset( $inverted_ids[$id] );

			$cache->save( $id, $map );
		}

		// For all leftovers, store them as empty to avoid
		// running individual SELECTS
		foreach ( $inverted_ids as $id => $pos ) {
			$cache->save( $id, [] );
		}

		$this->preloaded[$hash] = true;
	}

}
