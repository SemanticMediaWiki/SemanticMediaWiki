<?php

namespace SMW\SQLStore;

use SMW\MediaWiki\Collator;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TableFieldUpdater {

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var Collator
	 */
	private $collator;

	/**
	 * @since 3.0
	 *
	 * @param SQLStore $store
	 * @param Collator|null $collator
	 */
	public function __construct( SQLStore $store, Collator $collator = null ) {
		$this->store = $store;
		$this->collator = $collator;
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $id
	 * @param string $tz
	 */
	public function updateTouchedField( $id, $tz = 0 ) {

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
	 * @param integer $id
	 * @param string $searchKey
	 */
	public function updateSortField( $id, $searchKey ) {

		if ( $this->collator === null ) {
			$this->collator = Collator::singleton();
		}

		$connection = $this->store->getConnection( 'mw.db' );
		$connection->beginAtomicTransaction( __METHOD__ );

		// #2089 (MySQL 5.7 complained with "Data too long for column")
		$searchKey = mb_substr( $searchKey, 0, 254 );

		$connection->update(
			SQLStore::ID_TABLE,
			[
				'smw_sortkey' => $searchKey,
				'smw_sort'    => $this->collator->getSortKey( $searchKey ),
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
	 * @param integer $sid
	 * @param integer $rev_id
	 */
	public function updateRevField( $sid, $rev_id ) {

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
	 * @param integer $sid
	 * @param string $iw
	 * @param string $hash
	 */
	public function updateIwField( $sid, $iw, $hash ) {

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
