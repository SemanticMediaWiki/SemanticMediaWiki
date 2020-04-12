<?php

namespace SMW\SQLStore\TableBuilder\Examiner;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder;
use SMW\SetupFile;
use SMW\Maintenance\updateEntityCollation as UpdateEntityCollation;
use SMW\Utils\CliMsgFormatter;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class EntityCollation {

	use MessageReporterAwareTrait;

	/**
	 * @var SQLStore
	 */
	private $store;

	/**
	 * @var SetupFile
	 */
	private $setupFile;

	/**
	 * @var string
	 */
	private $entityCollation = '';

	/**
	 * @since 3.2
	 *
	 * @param SQLStore $store
	 */
	public function __construct( SQLStore $store ) {
		$this->store = $store;
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
	 * @param string $entityCollation
	 */
	public function setEntityCollation( string $entityCollation ) {
		$this->entityCollation = $entityCollation;
	}

	/**
	 * @since 3.2
	 */
	public function check() {

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
