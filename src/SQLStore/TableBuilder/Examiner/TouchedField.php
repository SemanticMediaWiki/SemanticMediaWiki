<?php

namespace SMW\SQLStore\TableBuilder\Examiner;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\SQLStore\SQLStore;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class TouchedField {

	use MessageReporterAwareTrait;

	/**
	 * @since 3.1
	 */
	public function __construct( private SQLStore $store ) {
	}

	/**
	 * @since 3.1
	 *
	 * @param array $opts
	 */
	public function check( array $opts = [] ) {
		$this->messageReporter->reportMessage( "Checking smw_touched field ...\n" );
		$connection = $this->store->getConnection( DB_PRIMARY );

		$row = $connection->newSelectQueryBuilder()
			->select( [ 'COUNT(smw_id) as count' ] )
			->from( SQLStore::ID_TABLE )
			->where( [
				'smw_touched IS NULL',
				$connection->expr( 'smw_iw', '!=', SMW_SQL3_SMWBORDERIW ),
			] )
			->caller( __METHOD__ )
			->fetchRow();

		if ( $row === false || $row->count == 0 ) {
			return $this->messageReporter->reportMessage( "   ... done.\n" );
		}

		$this->messageReporter->reportMessage( "   ... updating {$row->count} rows with default date ...\n" );

		$connection->newUpdateQueryBuilder()
			->update( SQLStore::ID_TABLE )
			->set( [ 'smw_touched' => $connection->timestamp( '1970-01-01 00:00:00' ) ] )
			->where( [ 'smw_touched IS NULL' ] )
			->caller( __METHOD__ )
			->execute();

		$connection->newUpdateQueryBuilder()
			->update( SQLStore::ID_TABLE )
			->set( [ 'smw_touched' => null ] )
			->where( [ 'smw_iw' => SMW_SQL3_SMWBORDERIW ] )
			->caller( __METHOD__ )
			->execute();

		$this->messageReporter->reportMessage( "   ... done.\n" );
	}

}
