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
	 */
	public function __construct( SQLStore $store, Collator $collator = null ) {
		$this->store = $store;
		$this->collator = $collator;

		if ( $this->collator === null ) {
			$this->collator = Collator::singleton();
		}
	}

	/**
	 * @since 3.0
	 *
	 * @param string $key1
	 * @param string $key2
	 *
	 * @return boolean
	 */
	public function canUpdateSortField( $key1, $key2 ) {
		return $this->collator->getSortKey( $key1 ) !== $this->collator->getSortKey( $key2 );
	}

	/**
	 * @since 3.0
	 *
	 * @param integer $id
	 * @param string $searchKey
	 *
	 * @return integer
	 */
	public function updateSortField( $id, $searchKey ) {

		$connection = $this->store->getConnection( 'mw.db' );

		// #2089 (MySQL 5.7 complained with "Data too long for column")
		$searchKey = mb_substr( $searchKey, 0, 254 );

		// http://www.mysqltutorial.org/mysql-distinct.aspx
		// Make the sort unique at the last position so that when GROUP by is used
		// it executes "... the GROUP BY clause sorts the result set" and as the
		// same time picks a uniqueue set avoiding a SELECT DISTINCT and a filesort
		$sort = $this->collator->getTruncatedSortKey( $searchKey, $id );

		$connection->beginAtomicTransaction( __METHOD__ );

		$connection->update(
			SQLStore::ID_TABLE,
			array(
				'smw_sortkey' => $searchKey,
				'smw_sort'    => $sort
			),
			array( 'smw_id' => $id ),
			__METHOD__
		);

		$connection->endAtomicTransaction( __METHOD__ );
	}

}
