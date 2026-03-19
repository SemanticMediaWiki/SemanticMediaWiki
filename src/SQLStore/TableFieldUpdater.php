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
	 * @since 3.1
	 *
	 * @param int $id
	 * @param string $tz
	 */
	public function updateTouchedField( $id, $tz = 0 ): void {
		$connection = $this->store->getConnection( 'mw.db' );
		$connection->beginAtomicTransaction( __METHOD__ );

		$connection->update(
			SQLStore::ID_TABLE,
			[
				'smw_touched' => $connection->timestamp( $tz )
			],
			[ 'smw_id' => $id ],
			__METHOD__
		);

		$connection->endAtomicTransaction( __METHOD__ );
	}

	/**
	 * @since 3.0
	 *
	 * @param int $id
	 * @param string $searchKey
	 */
	public function updateSortField( $id, $searchKey ): void {
		if ( $this->collator === null ) {
			$this->collator = Collator::singleton();
		}

		$connection = $this->store->getConnection( 'mw.db' );
		$connection->beginAtomicTransaction( __METHOD__ );

		$connection->update(
			SQLStore::ID_TABLE,
			[
				'smw_sortkey' => mb_strcut( $searchKey, 0, 255 ),
				'smw_sort'    => substr( $this->collator->getSortKey( $searchKey ), 0, 255 ),
				'smw_touched' => $connection->timestamp()
			],
			[ 'smw_id' => $id ],
			__METHOD__
		);

		$connection->endAtomicTransaction( __METHOD__ );
	}

	/**
	 * @since 3.0
	 *
	 * @param int $sid
	 * @param int $rev_id
	 */
	public function updateRevField( $sid, $rev_id ): void {
		$connection = $this->store->getConnection( 'mw.db' );

		$connection->update(
			SQLStore::ID_TABLE,
			[
				'smw_rev' => $rev_id,
				'smw_touched' => $connection->timestamp()
			],
			[
				'smw_id' => $sid
			],
			__METHOD__
		);
	}

	/**
	 * @since 3.1
	 *
	 * @param int $sid
	 * @param string $iw
	 * @param string $hash
	 */
	public function updateIwField( $sid, $iw, $hash ): void {
		$connection = $this->store->getConnection( 'mw.db' );

		$connection->update(
			SQLStore::ID_TABLE,
			[
				'smw_iw' => $iw,
				'smw_hash' => $hash
			],
			[
				'smw_id' => $sid
			],
			__METHOD__
		);
	}

}
