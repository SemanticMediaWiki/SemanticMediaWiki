<?php

namespace SMW\SQLStore;

use SMW\MediaWiki\Collator;

/**
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class TableFieldUpdater {

	/**
	 * @since 3.0
	 */
	public function __construct(
		private readonly SQLStore $store,
		private ?Collator $collator = null,
	) {
	}

	/**
	 * smw_sortkey and smw_sort hold 255 bytes on MySQL and MariaDB, or 255 characters with
	 * SMW_FIELDT_CHAR_NOCASE. Cutting at a character boundary keeps the value valid UTF-8,
	 * which PostgreSQL requires, and the same input always gives the same stored value.
	 *
	 * @since 7.4.1
	 */
	public static function fitSortColumn( string $value ): string {
		return mb_strcut( $value, 0, 255 );
	}

	/**
	 * @since 3.1
	 *
	 * @param int $id
	 * @param string|int $tz
	 *
	 * @return void
	 */
	public function updateTouchedField( $id, $tz = 0 ): void {
		$connection = $this->store->getConnection( 'mw.db' );
		$connection->beginAtomicTransaction( __METHOD__ );

		$connection->newUpdateQueryBuilder()
			->update( SQLStore::ID_TABLE )
			->set( [
				'smw_touched' => $connection->timestamp( $tz )
			] )
			->where( [ 'smw_id' => $id ] )
			->caller( __METHOD__ )
			->execute();

		$connection->endAtomicTransaction( __METHOD__ );
	}

	/**
	 * @since 3.0
	 *
	 * @param int $id
	 * @param string $searchKey
	 *
	 * @return void
	 */
	public function updateSortField( $id, $searchKey ): void {
		if ( $this->collator === null ) {
			$this->collator = Collator::singleton();
		}

		$sortKey = self::fitSortColumn( (string)$searchKey );

		$connection = $this->store->getConnection( 'mw.db' );
		$connection->beginAtomicTransaction( __METHOD__ );

		$connection->newUpdateQueryBuilder()
			->update( SQLStore::ID_TABLE )
			->set( [
				'smw_sortkey' => $sortKey,
				'smw_sort'    => self::fitSortColumn( $this->collator->armoredSortKey( $sortKey ) ),
				'smw_touched' => $connection->timestamp()
			] )
			->where( [ 'smw_id' => $id ] )
			->caller( __METHOD__ )
			->execute();

		$connection->endAtomicTransaction( __METHOD__ );
	}

	/**
	 * @since 3.0
	 *
	 * @param int $sid
	 * @param int $rev_id
	 *
	 * @return void
	 */
	public function updateRevField( $sid, $rev_id ): void {
		$connection = $this->store->getConnection( 'mw.db' );

		$connection->newUpdateQueryBuilder()
			->update( SQLStore::ID_TABLE )
			->set( [
				'smw_rev' => $rev_id,
				'smw_touched' => $connection->timestamp()
			] )
			->where( [
				'smw_id' => $sid
			] )
			->caller( __METHOD__ )
			->execute();
	}

	/**
	 * @since 3.1
	 *
	 * @param int $sid
	 * @param string $iw
	 * @param string $hash
	 *
	 * @return void
	 */
	public function updateIwField( $sid, $iw, $hash ): void {
		$connection = $this->store->getConnection( 'mw.db' );

		$connection->newUpdateQueryBuilder()
			->update( SQLStore::ID_TABLE )
			->set( [
				'smw_iw' => $iw,
				'smw_hash' => $connection->escape_bytea( $hash )
			] )
			->where( [
				'smw_id' => $sid
			] )
			->caller( __METHOD__ )
			->execute();
	}

}
