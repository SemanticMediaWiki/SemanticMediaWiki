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
use SMW\SetupFile;
use SMW\Utils\CliMsgFormatter;
use SMW\MediaWiki\HookDispatcher;
use Onoi\MessageReporter\MessageReporter;
use SMW\Maintenance\MaintenanceCheck;

/**
 * Load the required class
 */
if ( getenv( 'MW_INSTALL_PATH' ) !== false ) {
	require_once getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php';
} else {
	require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class updateEntityCollation extends \Maintenance {

	/**
	 * Incomplete task message
	 */
	const ENTITY_COLLATION_INCOMPLETE = 'smw-updateentitycollation-incomplete';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var HookDispatcher
	 */
	private $hookDispatcher;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @var int
	 */
	private $lastId = 0;

	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Update the smw_sort field (relying on the $smwgEntityCollation setting)';
		$this->addOption( 's', 'ID starting point', false, true );
	}

	/**
	 * @since 3.2
	 *
	 * @param HookDispatcher $hookDispatcher
	 */
	public function setHookDispatcher( HookDispatcher $hookDispatcher ) {
		$this->hookDispatcher = $hookDispatcher;
	}

	/**
	 * @since 3.2
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 1.9
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		$this->output( $message );
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {

		if ( ( $maintenanceCheck = new MaintenanceCheck() )->canExecute() === false ) {
			exit ( $maintenanceCheck->getMessage() );
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();
		$setupFile = $applicationFactory->singleton( 'SetupFile' );

		$this->store = $applicationFactory->getStore(
			SQLStore::class
		);

		if ( $this->hookDispatcher === null ) {
			$this->hookDispatcher = $applicationFactory->getHookDispatcher();
		}

		if ( $this->messageReporter === null ) {
			$this->messageReporter = $maintenanceFactory->newMessageReporter( [ $this, 'reportMessage' ] );
		}

		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->head()
		);

		$intl = 'N/A';

		if ( extension_loaded( 'intl' ) ) {
			$intl = phpversion( 'intl' ) . ' / ' . INTL_ICU_VERSION;
		}

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->twoCols( 'Intl / ICU:', $intl )
		);

		$smwgEntityCollation = $applicationFactory->getSettings()->get( 'smwgEntityCollation' );
		$wgCategoryCollation = $GLOBALS['wgCategoryCollation'];

		if ( $smwgEntityCollation !== $wgCategoryCollation ) {
			$this->informAboutDifferences( $smwgEntityCollation, $wgCategoryCollation );
		}

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Collation update(s)' )
		);

		$this->messageReporter->reportMessage(
			"\nRunning `$smwgEntityCollation` update ...\n"
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->firstCol( '... fetching from table ...', 3 )
		);

		$rows = $this->fetchRows();
		$count = $rows->numRows();

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->twoCols( "... found `smw_sort` field records ...", "$count (rows)", 3 )
		);

		$this->runUpdate( $rows, $count );
		$this->messageReporter->reportMessage( "\n   ... done.\n" );

		$setupFile->removeIncompleteTask( self::ENTITY_COLLATION_INCOMPLETE );

		$setupFile->set(
			[
				SetupFile::ENTITY_COLLATION => $smwgEntityCollation
			]
		);

		$this->hookDispatcher->onAfterUpdateEntityCollationComplete( $this->store, $this->messageReporter );
	}

	private function informAboutDifferences( $smwgEntityCollation, $wgCategoryCollation ) {

		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Notice' )
		);

		$text = [
			"The `smwgEntityCollation` and `wgCategoryCollation` have different",
			"collation settings and may therefore result in an inconsitent sorting",
			"display for entities."
		];

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n\n"
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->oneCol( 'Collation settings ...' )
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->twoCols( '... `$smwgEntityCollation`', $smwgEntityCollation, 3, '.' )
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->twoCols( '... `$wgCategoryCollation`', $wgCategoryCollation, 3, '.' )
		);
	}

	private function fetchRows() {

		$connection = $this->store->getConnection( 'mw.db' );

		$this->lastId = (int)$connection->selectField(
			SQLStore::ID_TABLE,
			'MAX(smw_id)',
			'',
			__METHOD__
		);

		$condition = '';

		$condition .= " smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWIW_OUTDATED );
		$condition .= " AND smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWDELETEIW );

		if ( $this->hasOption( 's' ) ) {
			$condition .= ' AND smw_id > ' . $connection->addQuotes( $this->getOption( 's' ) );
		}

		return $connection->select(
			SQLStore::ID_TABLE,
			[
				'smw_id',
				'smw_title',
				'smw_sortkey'
			],
			$condition,
			__METHOD__,
			[ 'ORDER BY' => 'smw_id' ]
		);
	}

	private function runUpdate( $rows, $count ) {

		$tableFieldUpdater = new TableFieldUpdater(
			$this->store
		);

		$cliMsgFormatter = new CliMsgFormatter();
		$property = new DIProperty( '_SKEY' );
		$i = 0;

		foreach ( $rows as $row ) {

			if ( $row->smw_title === '' ) {
				continue;
			}

			$dataItem = $this->store->getObjectIds()->getDataItemById(
				$row->smw_id
			);

			$pv = $this->store->getPropertyValues( $dataItem, $property );
			$search = $this->getSortKey( $row, $pv );

			if ( $search === '' && $row->smw_title !== '' ) {
				$search = str_replace( '_', ' ', $row->smw_title );
			}

			$progress = $cliMsgFormatter->progressCompact( ++$i, $count, $row->smw_id, $this->lastId );

			$this->messageReporter->reportMessage(
				$cliMsgFormatter->twoColsOverride( "... updating entity (current/last) ...", $progress, 3 )
			);

			$tableFieldUpdater->updateSortField( $row->smw_id, $search );
		}
	}

	private function getSortKey( $row, $pv ) {

		if ( $pv !== [] ) {
			return end( $pv )->getString();
		}

		if ( $row->smw_title[0] !== '_' ) {
			return $row->smw_sortkey;
		}

		try {
			$property = new DIProperty( $row->smw_title );
		} catch ( PredefinedPropertyLabelMismatchException $e ) {
			return $row->smw_sortkey;
		}

		return $property->getCanonicalLabel();
	}

}

$maintClass = updateEntityCollation::class;
require_once( RUN_MAINTENANCE_IF_MAIN );
