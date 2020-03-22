<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use SMW\ApplicationFactory;
use SMW\SQLStore\SQLStore;
use SMW\Setup;
use SMW\Store;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Utils\CliMsgFormatter;
use SMW\Exception\PredefinedPropertyLabelMismatchException;

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
 * @since 3.1
 *
 * @author mwjames
 */
class purgeEntityCache extends \Maintenance {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var EntityCache
	 */
	private $entityCache;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @var integer
	 */
	private $lastId = 0;

	/**
	 * @since 3.1
	 */
	public function __construct() {
		parent::__construct();
		$this->addDescription( "Purge cache entries for known entities and their associated data." );
	}

	/**
	 * @since 3.1
	 *
	 * @param MessageReporter $messageReporter
	 */
	public function setMessageReporter( MessageReporter $messageReporter ) {
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 3.1
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {

		if ( $this->messageReporter !== null ) {
			return $this->messageReporter->reportMessage( $message );
		}

		$this->output( $message );
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {

		if ( $this->canExecute() !== true ) {
			exit;
		}

		$cliMsgFormatter = new CliMsgFormatter();
		$applicationFactory = ApplicationFactory::getInstance();

		$this->store = $applicationFactory->getStore();
		$this->entityCache = $applicationFactory->getEntityCache();

		$this->reportMessage(
			"\n" . $cliMsgFormatter->head()
		);

		$this->reportMessage(
			$cliMsgFormatter->section( 'About' )
		);

		$text = [
			"The script purges cache entries and their associate data for actively",
			"used entities."
		];

		$this->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$this->reportMessage(
			$cliMsgFormatter->section( 'Cache purge' )
		);

		$this->reportMessage( "\nSelecting entities ...\n" );

		$this->reportMessage(
			$cliMsgFormatter->firstCol( "... fetching rows from table ...", 3 )
		);

		$rows = $this->fetchRows();

		$this->reportMessage(
			$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
		);

		$this->doPurge( $rows );

		$this->reportMessage( "   ... done.\n" );

		return true;
	}

	private function fetchRows() {

		$connection = $this->store->getConnection( 'mw.db' );

		$this->lastId = (int)$connection->selectField(
			SQLStore::ID_TABLE,
			'MAX(smw_id)',
			'',
			__METHOD__
		);

		return $connection->select(
			SQLStore::ID_TABLE,
			[
				'smw_id',
				'smw_title',
				'smw_namespace',
				'smw_iw',
				'smw_subobject'
			],
			[
				"smw_subobject=''",
				'smw_iw != ' . $connection->addQuotes( SMW_SQL3_SMWDELETEIW )
			],
			__METHOD__,
			[ 'ORDER BY' => 'smw_id' ]
		);
	}

	private function canExecute() {

		if ( !Setup::isEnabled() ) {
			return $this->reportMessage(
				"\nYou need to have SMW enabled in order to run the maintenance script!\n"
			);
		}

		if ( !Setup::isValid( true ) ) {
			return $this->reportMessage(
				"\nYou need to run `update.php` or `setupStore.php` first before continuing\n" .
				"with this maintenance task!\n"
			);
		}

		return true;
	}

	private function doPurge( \Iterator $rows ) {

		$cliMsgFormatter = new CliMsgFormatter();

		$connection = $this->store->getConnection( 'mw.db' );

		$count = $rows->numRows();
		$i = 0;

		if ( $count == 0 ) {
			return $this->reportMessage( "   ... no entities selected ...\n"  );
		}

		$this->reportMessage(
			$cliMsgFormatter->twoCols( "... entities matched ...", "(rows) $count", 3 )
		);

		foreach ( $rows as $row ) {
			$i++;

			$msg = $cliMsgFormatter->progressCompact( $i, $count, $row->smw_id, $this->lastId );

			$this->reportMessage(
				$cliMsgFormatter->twoColsOverride( "... invalidating cache for ...", $msg, 3 )
			);

			$this->entityCache->invalidate( $this->newFromRow( $row ) );
		}

		$this->reportMessage( "\n"  );
	}

	public function newFromRow( $row ) {

		$namespace = (int)$row->smw_namespace;
		$title = $row->smw_title;

		if ( $namespace === SMW_NS_PROPERTY ) {
			try {
				$property = DIProperty::newFromUserLabel( $row->smw_title );
				$title = str_replace( ' ', '_', $property->getLabel() );
			} catch( PredefinedPropertyLabelMismatchException $e ) {
				//
			}
		}

		$subject = new DIWikiPage(
			$title,
			$namespace,
			$row->smw_iw,
			$row->smw_subobject
		);

		return $subject;
	}

}

$maintClass = purgeEntityCache::class;
require_once( RUN_MAINTENANCE_IF_MAIN );
