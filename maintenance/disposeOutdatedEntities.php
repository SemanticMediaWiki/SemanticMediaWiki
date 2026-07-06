<?php

namespace SMW\Maintenance;

use MediaWiki\Maintenance\Maintenance;
use Onoi\MessageReporter\CallbackMessageReporter;
use Onoi\MessageReporter\MessageReporter;
use SMW\Maintenance\DataRebuilder\OutdatedDisposer;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Setup;
use SMW\Utils\CliMsgFormatter;

/**
 * Load the required class
 */
// @codeCoverageIgnoreStart
if ( getenv( 'MW_INSTALL_PATH' ) !== false ) {
	require_once getenv( 'MW_INSTALL_PATH' ) . '/maintenance/Maintenance.php';
} else {
	require_once __DIR__ . '/../../../maintenance/Maintenance.php';
}
// @codeCoverageIgnoreEnd

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class disposeOutdatedEntities extends Maintenance {

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @since 3.2
	 */
	public function __construct() {
		parent::__construct();
		$this->addDescription( "Dispose of outdated entities." );
		$this->addOption( 'with-maintenance-log', 'Add log entry to `Special:Log` about the maintenance run.', false );
		$this->addOption( 'of', '<N> Total number of parallel shards to split the disposal across.', false, true );
		$this->addOption( 'shard', '<k> Zero-based index (0..N-1) of this shard; requires --of.', false, true );
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
	 * @since 3.2
	 *
	 * @param string $message
	 */
	public function reportMessage( $message ) {
		$this->output( $message );
	}

	/**
	 * Validates the --of/--shard option pair. Returned as a string (rather than
	 * calling fatalError directly) so the rule is unit-testable without driving
	 * the maintenance harness, whose fatalError behaviour varies across
	 * MediaWiki versions.
	 *
	 * @since 7.0.0
	 *
	 * @return string|null Error message when the configuration is invalid, null when acceptable
	 */
	public function getShardConfigError( bool $hasOf, bool $hasShard, int $of, int $shard ): ?string {
		if ( $hasOf !== $hasShard ) {
			return '--of and --shard must be used together.';
		}

		if ( $of < 1 || $shard < 0 || $shard >= $of ) {
			return 'Invalid shard configuration: require --of >= 1 and 0 <= --shard < --of.';
		}

		return null;
	}

	/**
	 * @see Maintenance::execute
	 */
	public function execute() {
		if ( $this->canExecute() !== true ) {
			exit;
		}

		$applicationFactory = ApplicationFactory::getInstance();
		$maintenanceFactory = $applicationFactory->newMaintenanceFactory();

		$maintenanceHelper = $maintenanceFactory->newMaintenanceHelper();
		$maintenanceHelper->initRuntimeValues();

		$title = $this->getServiceContainer()->getTitleFactory()->newFromText( __METHOD__ );

		$of = $this->hasOption( 'of' ) ? (int)$this->getOption( 'of' ) : 1;
		$shard = $this->hasOption( 'shard' ) ? (int)$this->getOption( 'shard' ) : 0;

		$error = $this->getShardConfigError(
			$this->hasOption( 'of' ),
			$this->hasOption( 'shard' ),
			$of,
			$shard
		);

		if ( $error !== null ) {
			$this->fatalError( $error . "\n" );
		}

		$outdatedDisposer = new OutdatedDisposer(
			$applicationFactory->newJobFactory()->newEntityIdDisposerJob( $title ),
			$applicationFactory->getIteratorFactory()
		);

		$outdatedDisposer->setShard( $shard, $of );

		if ( $this->messageReporter === null ) {
			$this->messageReporter = new CallbackMessageReporter( [ $this, 'reportMessage' ] );
		}

		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->head()
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'About' )
		);

		$text = [
			"This script will remove outdated entities and entities rendered",
			"invalid due to redeclared namespace settings. It will also dispose of",
			"query link entries from tables that no longer hold a valid entity reference",
			"in Semantic MediaWiki."
		];

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Outdated entitie(s)' ) . "\n"
		);

		$outdatedDisposer->setMessageReporter( $this->messageReporter );
		$outdatedDisposer->run();

		if ( $this->hasOption( 'with-maintenance-log' ) ) {
			$maintenanceLogger = $maintenanceFactory->newMaintenanceLogger( 'DisposeOutdatedEntitiesLogger' );
			$runtimeValues = $maintenanceHelper->getRuntimeValues();

			$log = [
				'Memory used' => $runtimeValues['memory-used'],
				'Time used' => $runtimeValues['humanreadable-time']
			];

			$maintenanceLogger->logFromArray( $log );
		}

		return true;
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

}

// @codeCoverageIgnoreStart
$maintClass = disposeOutdatedEntities::class;
require_once RUN_MAINTENANCE_IF_MAIN;
// @codeCoverageIgnoreEnd
