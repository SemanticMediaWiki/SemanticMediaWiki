<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporter;
use SMW\ApplicationFactory;
use SMW\SQLStore\SQLStore;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Setup;
use Title;

$basePath = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $basePath . '/maintenance/Maintenance.php';

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class UpdateQueryDependencies extends \Maintenance {

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @since 3.1
	 */
	public function __construct() {
		$this->mDescription = 'Update queries and query dependencies.';
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
			$this->dieMessage(
				"\nYou need to have SMW enabled in order to run the maintenance script!\n"
			);
		}

		if ( !Setup::isValid( true ) ) {
			$this->dieMessage(
				"\nYou need to run `update.php` or `setupStore.php` first before continuing" .
				"\nwith any maintenance tasks!\n"
			);
		}

		$this->reportMessage(
			"\nThe script schedules updates for entities known to contain queries." .
			"\nA re-parse of the entity content will ensure to find new or missing" .
			"\nquery dependencies and trigger updates to the `smw_query_links`" .
			"\ntable.\n"
		);

		$settings = ApplicationFactory::getInstance()->getSettings();

		if ( $settings->get( 'smwgQueryProfiler' ) === false ) {
			return $this->reportMessage(
				"\nThe `smwgQueryProfiler` has been disabled therefore making it impossible" .
				"\nto track embedded queries.\n"
			);
		}

		if ( $settings->get( 'smwgEnabledQueryDependencyLinksStore' ) === false ) {
			$this->reportMessage(
				"\nThe `smwgEnabledQueryDependencyLinksStore` is disabled therefore" .
				"\nno query dependency updates (see `smw_query_links` table) will occur.\n"
			);
		}

		return $this->runUpdate();
	}

	/**
	 * @see Maintenance::addDefaultParams
	 *
	 * @since 3.0
	 */
	protected function addDefaultParams() {
		parent::addDefaultParams();
	}

	private function dieMessage( $message ) {
		$this->reportMessage( $message );
		exit;
	}

	private function runUpdate() {

		$applicationFactory = ApplicationFactory::getInstance();
		$store = $applicationFactory->getStore( SQLStore::class );

		$jobFactory = $applicationFactory->newJobFactory();
		$connection = $store->getConnection( 'mw.db' );

		$tableName = $store->getPropertyTableInfoFetcher()->findTableIdForProperty(
			new DIProperty( '_ASK' )
		);

		$res = $connection->select(
			[ SQLStore::ID_TABLE, $tableName . ' AS p' ],
			[
				'smw_id',
				'smw_title',
				'smw_namespace'
			],
			[
				'smw_iw!=' . $connection->addQuotes( SMW_SQL3_SMWIW_OUTDATED ),
				'smw_iw!=' . $connection->addQuotes( SMW_SQL3_SMWDELETEIW ),
			],
			__METHOD__,
			[
				'GROUP BY' => 'smw_id'
			],
			[
				$tableName . ' AS p' => [ 'INNER JOIN', [ 'p.s_id=smw_id' ] ],
			]
		);

		$expected = $res->numRows();
		$i = 0;

		$this->reportMessage(
			"\nPerforming the update ..."
		);

		$this->reportMessage( "\n   ... found $expected entities ..." );
		$this->reportMessage( "\n" );

		foreach ( $res as $row ) {
			$i++;

			$this->reportMessage(
				"\r". sprintf( "%-55s%s", "   ... update ...", sprintf( "%4.0f%% (%s/%s)", ( $i / $expected ) * 100, $i, $expected ) )
			);

			$updateJob = $jobFactory->newUpdateJob(
				Title::makeTitleSafe( $row->smw_namespace, $row->smw_title ),
				[
					'origin' => 'updateQueryDependencies.php'
				]
			);

			$updateJob->run();
		}

		$this->reportMessage( "\n" );

		return true;
	}

}

$maintClass = 'SMW\Maintenance\UpdateQueryDependencies';
require_once( RUN_MAINTENANCE_IF_MAIN );
