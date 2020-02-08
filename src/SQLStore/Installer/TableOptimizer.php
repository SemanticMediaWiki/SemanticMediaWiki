<?php

namespace SMW\SQLStore\Installer;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\SetupFile;
use SMW\Utils\CliMsgFormatter;
use SMW\SQLStore\TableBuilder;
use DateTime;
use DateTimeZone;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class TableOptimizer {

	use MessageReporterAwareTrait;

	/**
	 * @var TableBuilder
	 */
	private $tableBuilder;

	/**
	 * @var SetupFile
	 */
	private $setupFile;

	/**
	 * @since 3.2
	 *
	 * @param TableBuilder $tableBuilder
	 */
	public function __construct( TableBuilder $tableBuilder ) {
		$this->tableBuilder = $tableBuilder;
	}

	/**
	 * @since 3.2
	 *
	 * @param SetupFile $setupFile
	 */
	public function setSetupFile( SetupFile $setupFile ) {
		$this->setupFile = $setupFile;
	}

	/**
	 * @since 3.2
	 *
	 * @param array $tables
	 */
	public function runForTables( array $tables ) {

		$cliMsgFormatter = new CliMsgFormatter();

		$text = [
			'The optimization task can take a moment to complete and depending',
			'on the database backend, tables can be locked during the operation.'
		];

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Core table(s)', 6, '-', true ) . "\n"
		);

		$custom = false;
		$this->messageReporter->reportMessage( "Checking table ...\n" );

		foreach ( $tables as $table ) {

			if ( !$custom && !$table->isCoreTable() ) {
				$custom = true;

				$this->messageReporter->reportMessage( "   ... done.\n" );

				$this->messageReporter->reportMessage(
					$cliMsgFormatter->section( 'Custom table(s)', 6, '-', true ) . "\n"
				);

				$this->messageReporter->reportMessage( "Checking table ...\n" );
			}

			$this->tableBuilder->optimize( $table );
		}

		$this->messageReporter->reportMessage( "   ... done.\n" );

		$dateTimeUtc = new DateTime( 'now', new DateTimeZone( 'UTC' ) );

		$this->setupFile->set(
			[
				SetupFile::LAST_OPTIMIZATION_RUN => $dateTimeUtc->format( 'Y-m-d h:i' )
			]
		);
	}

}
