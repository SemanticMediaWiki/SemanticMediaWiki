<?php

namespace SMW\Maintenance;

use SMW\ApplicationFactory;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableFieldUpdater;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMWDataItem as DataItem;
use SMW\Exception\PredefinedPropertyLabelMismatchException;
use SMW\Setup;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv('MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class UpdateEntityCollation extends \Maintenance {

	public function __construct() {
		$this->mDescription = 'Update the smw_sort field (relying on the $smwgEntityCollation setting)';
		$this->addOption( 's', 'ID starting point', false, true );

		parent::__construct();
	}

	/**
	 * @see Maintenance::addDefaultParams
	 *
	 * @since 3.0
	 */
	protected function addDefaultParams() {

		parent::addDefaultParams();
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {

		if ( !Setup::isEnabled() ) {
			$this->reportMessage( "\nYou need to have SMW enabled in order to run the maintenance script!\n" );
			exit;
		}

		if ( !Setup::isValid( true ) ) {
			$this->reportMessage( "\nYou need to run `update.php` or `setupStore.php` first before continuing\nwith any maintenance tasks!\n" );
			exit;
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$store = $applicationFactory->getStore( 'SMW\SQLStore\SQLStore' );

		$connection = $store->getConnection( 'mw.db' );
		$tableFieldUpdater = new TableFieldUpdater( $store );

		$condition = " smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWIW_OUTDATED ) . " AND smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWDELETEIW );
		$i = 1;

		if ( $this->hasOption( 's' ) ) {
			$i = $this->getOption( 's' );
			$condition .= ' AND smw_id > ' . $connection->addQuotes( $this->getOption( 's' ) );
		}

		$res = $connection->select(
			SQLStore::ID_TABLE,
			[
				'smw_id',
				'smw_title',
				'smw_sortkey'
			],
			$condition,
			__METHOD__
		);

		$expected = $res->numRows() + $i;

		if ( $applicationFactory->getSettings()->get( 'smwgEntityCollation' ) !== $GLOBALS['wgCategoryCollation'] ) {
			$this->reportMessage(
				"\n" . 'The setting of $smwgEntityCollation and $wgCategoryCollation are different' . "\n" .
				'and may result in an inconsitent sorting display for entities.' . "\n"
			);

			$this->reportMessage( "\n" . '$smwgEntityCollation: ' . $applicationFactory->getSettings()->get( 'smwgEntityCollation' ) );
			$this->reportMessage( "\n" . '$wgCategoryCollation: ' . $GLOBALS['wgCategoryCollation'] . "\n" );
		}

		$this->reportMessage(
			"\nPerforming the update ..."
		);

		$this->reportMessage( "\n   ... selecting $expected rows ..." );
		$this->reportMessage( "\n" );

		$this->doUpdate( $store, $tableFieldUpdater, $res, $i, $expected );
		$this->reportMessage( "\n" );
	}

	private function doUpdate( $store, $tableFieldUpdater, $res, $i, $expected ) {
		$property = new DIProperty( '_SKEY' );

		foreach ( $res as $row ) {

			if ( $row->smw_title === '' ) {
				continue;
			}

			$i++;

			$dataItem = $store->getObjectIds()->getDataItemById( $row->smw_id );
			$pv = $store->getPropertyValues( $dataItem, $property );

			$search = $this->getSortKey( $row, $pv );

			if ( $search === '' && $row->smw_title !== '' ) {
				$search = str_replace( '_', ' ', $row->smw_title );
			}

			$this->reportMessage(
				"\r". sprintf( "%-35s%s", "   ... updating document no.", sprintf( "%4.0f%% (%s/%s)", ( $i / $expected ) * 100, $i, $expected ) )
			);

			$tableFieldUpdater->updateSortField( $row->smw_id, $search );
		}
	}

	private function getSortKey( $row, $pv ) {

		if ( $pv !== [] ) {
			return end( $pv )->getString();
		}

		if ( $row->smw_title{0} !== '_' ) {
			return $row->smw_sortkey;
		}

		try {
			$property = new DIProperty( $row->smw_title );
		} catch ( PredefinedPropertyLabelMismatchException $e ) {
			return $row->smw_sortkey;
		}

		return $property->getCanonicalLabel();
	}

	/**
	 * @see Maintenance::reportMessage
	 *
	 * @since 1.9
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		$this->output( $message );
	}

}

$maintClass = 'SMW\Maintenance\UpdateEntityCollation';
require_once( RUN_MAINTENANCE_IF_MAIN );
