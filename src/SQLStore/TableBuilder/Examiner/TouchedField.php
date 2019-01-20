<?php

namespace SMW\SQLStore\TableBuilder\Examiner;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\SQLStore\SQLStore;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class TouchedField {

	use MessageReporterAwareTrait;

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
	 * @param array $opts
	 */
	public function check( array $opts = [] ) {

		$this->messageReporter->reportMessage( "Checking smw_touched field ...\n" );
		$connection = $this->store->getConnection( DB_MASTER );

		$row = $connection->selectRow(
			SQLStore::ID_TABLE,
			[
				'COUNT(smw_id) as count'
			],
			[
				'smw_touched IS NULL',
				'smw_iw!=' . $connection->addQuotes( SMW_SQL3_SMWBORDERIW )
			],
			__METHOD__
		);

		if ( $row === false || $row->count == 0 ) {
			return $this->messageReporter->reportMessage( "   ... done.\n" );
		}

		$this->messageReporter->reportMessage( "   ... updating {$row->count} rows with default date ...\n" );

		$connection->update(
			SQLStore::ID_TABLE,
			[
				'smw_touched' => $connection->timestamp( '1970-01-01 00:00:00' )
			],
			[
				'smw_touched IS NULL'
			],
			__METHOD__
		);

		$connection->update(
			SQLStore::ID_TABLE,
			[
				'smw_touched' => NULL
			],
			[
				'smw_iw' => SMW_SQL3_SMWBORDERIW
			],
			__METHOD__
		);

		$this->messageReporter->reportMessage( "   ... done.\n" );
	}

}
