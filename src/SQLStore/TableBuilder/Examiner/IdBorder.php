<?php

namespace SMW\SQLStore\TableBuilder\Examiner;

use SMW\MediaWiki\Database;
use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\SQLStore\SQLStore;
use RuntimeException;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class IdBorder {

	use MessageReporterAwareTrait;

	/**
	 * Upper bound
	 */
	const LEGACY_BOUND = 'legacy.bound';

	/**
	 * Upper bound
	 */
	const UPPER_BOUND = 'upper.bound';

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @since 3.1
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $params
	 */
	public function check( array $params = [] ) {

		if ( !isset( $params[self::UPPER_BOUND] ) ) {
			throw new RuntimeException( "Missing an upper bound!" );
		}

		if ( !isset( $params[self::LEGACY_BOUND] ) ) {
			throw new RuntimeException( "Missing a legacy bound!" );
		}

		$this->messageReporter->reportMessage( "Checking internal properties border ...\n" );

		$this->findAndMove(
			$params[self::UPPER_BOUND],
			$params[self::LEGACY_BOUND]
		);

		$this->messageReporter->reportMessage( "   ... done.\n" );
	}

	private function findAndMove( $upperbound, $legacyBound ) {

		$connection = $this->store->getConnection( DB_MASTER );
		$row = false;
		$hasUpperBound = false;

		$rows = $connection->select(
			SQLStore::ID_TABLE,
			[
				'smw_id'
			],
			[
				'smw_iw' => SMW_SQL3_SMWBORDERIW
			],
			__METHOD__
		);

		foreach ( $rows as $row ) {
			if ( $row->smw_id == $upperbound ) {
				$hasUpperBound = true;
			} else {
				$connection->delete(
					SQLStore::ID_TABLE,
					[
						'smw_id' => $row->smw_id
					],
					__METHOD__
				);
			}
		}

		if ( $hasUpperBound ) {
			return $this->messageReporter->reportMessage( "   ... space for internal properties allocated ...\n" );
		}

		if ( $row === false ) {
			$currentUpperbound = $legacyBound;
		} else {
			$currentUpperbound = $row->smw_id;
		}

		$this->messageReporter->reportMessage( "   ... allocating space for internal properties ...\n" );
		$this->store->getObjectIds()->moveSMWPageID( $upperbound );

		$connection->insert(
			SQLStore::ID_TABLE,
			[
				'smw_id' => $upperbound,
				'smw_title' => '',
				'smw_namespace' => 0,
				'smw_iw' => SMW_SQL3_SMWBORDERIW,
				'smw_subobject' => '',
				'smw_sortkey' => ''
			],
			__METHOD__
		);

		if ( $currentUpperbound < $upperbound ) {
			$this->move( $currentUpperbound, $upperbound );
		}
	}

	private function move( $old, $new ) {

		$this->messageReporter->reportMessage(
			"   ... moving from $old to $new as upper bound (may take a moment) ..."
		);

		$this->messageReporter->reportMessage( "       " );
		$entityTable = $this->store->getObjectIds();

		for ( $i = $old; $i < $new; $i++ ) {

			if ( ( $i - $old ) % 60 === 0 ) {
				$this->messageReporter->reportMessage( "\n       " );
			}

			$this->messageReporter->reportMessage( "." );
			$entityTable->moveSMWPageID( $i );
		}

		$this->messageReporter->reportMessage( "\n" );
	}

}
