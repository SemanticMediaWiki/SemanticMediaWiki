<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use SMW\ApplicationFactory;
use SMW\SQLStore\SQLStore;
use SMW\Setup;
use SMW\Store;
use SMW\DIWikiPage;
use SMW\DIProperty;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv('MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class PurgeEntityCache extends \Maintenance {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @since 3.1
	 */
	public function __construct() {
		$this->mDescription = "Purge cache entries for known entities and their associated data.";
		parent::__construct();
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

		if ( !Setup::isEnabled() ) {
			$this->reportMessage( "\nYou need to have Semantic MediaWiki enabled in order to run the maintenance script!\n" );
			exit;
		}

		$this->store = ApplicationFactory::getInstance()->getStore();

		$this->reportMessage(
			"\nThe script purges cache entries and their associate data for actively\n" .
			"used entities.\n"
		);

		$this->reportMessage( "\nSelecting entities ...\n" );
		$this->doPurge( $this->fetchRows() );

		$this->reportMessage( "   ... done.\n" );

		return true;
	}

	private function fetchRows() {

		$connection = $this->store->getConnection( 'mw.db' );

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

	private function doPurge( \Iterator $rows ) {

		$entityCache = ApplicationFactory::getInstance()->getEntityCache();
		$connection = $this->store->getConnection( 'mw.db' );

		$count = $rows->numRows();
		$i = 0;

		if ( $count == 0 ) {
			return $this->reportMessage( "   ... no entities selected ...\n"  );
		}

		$this->reportMessage( "   ... counting $count rows ...\n"  );

		foreach ( $rows as $row ) {
			$namespace = (int)$row->smw_namespace;

			if (  $namespace === SMW_NS_PROPERTY ) {
				$property = DIProperty::newFromUserLabel( $row->smw_title );
				$subject = $property->getCanonicalDiWikiPage();
			} else {
				$subject = new DIWikiPage(
					$row->smw_title,
					$namespace,
					$row->smw_iw,
					$row->smw_subobject
				);
			}

			$this->reportMessage(
				$this->progress( $row->smw_id, $i++, $count )
			);

			$entityCache->invalidate( $subject );
		}

		$this->reportMessage( "\n"  );
	}

	/**
	 * @see Maintenance::addDefaultParams
	 */
	protected function addDefaultParams() {
		parent::addDefaultParams();
	}

	private function progress( $id, $i, $count ) {
		return "\r". sprintf( "%-35s%s", "   ... purging document no.", sprintf( "%s (%1.0f%%)", $id, round( ( $i / $count ) * 100 ) ) );
	}

}

$maintClass = 'SMW\Maintenance\PurgeEntityCache';
require_once( RUN_MAINTENANCE_IF_MAIN );
