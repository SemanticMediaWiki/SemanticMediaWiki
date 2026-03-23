<?php

namespace SMW\SQLStore\TableBuilder\Examiner;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\Maintenance\updateEntityCollation;
use SMW\SetupFile;
use SMW\SQLStore\SQLStore;
use SMW\Utils\CliMsgFormatter;

/**
 * @license GPL-2.0-or-later
 * @since 3.2
 *
 * @author mwjames
 */
class EntityCollation {

	use MessageReporterAwareTrait;

	private ?SetupFile $setupFile = null;

	private string $entityCollation = '';

	/**
	 * @since 3.2
	 */
	public function __construct( private SQLStore $store ) {
	}

	/**
	 * @since 3.2
	 *
	 * @param SetupFile $setupFile
	 */
	public function setSetupFile( SetupFile $setupFile ): void {
		$this->setupFile = $setupFile;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $entityCollation
	 */
	public function setEntityCollation( string $entityCollation ): void {
		$this->entityCollation = $entityCollation;
	}

	/**
	 * @since 3.2
	 */
	public function check(): void {
		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->firstCol( "Checking entity collation type ..." )
		);

		$entityCollation = $this->setupFile->get( SetupFile::ENTITY_COLLATION ) ?? 'identity';

		if ( $this->entityCollation !== $entityCollation ) {

			$this->messageReporter->reportMessage(
				"\n   ... adding incomplete task for entity collation conversion ...\n"
			);

			$this->setupFile->addIncompleteTask( UpdateEntityCollation::ENTITY_COLLATION_INCOMPLETE );

		} else {
			$this->messageReporter->reportMessage(
				$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
			);
		}

		$this->messageReporter->reportMessage( "   ... done.\n" );
	}

}
