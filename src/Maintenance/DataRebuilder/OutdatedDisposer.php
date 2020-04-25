<?php

namespace SMW\Maintenance\DataRebuilder;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\MediaWiki\Jobs\EntityIdDisposerJob;
use SMW\IteratorFactory;
use SMW\Utils\CliMsgFormatter;
use Title;

/**
 * @private
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class OutdatedDisposer {

	use MessageReporterAwareTrait;

	/**
	 * @var EntityIdDisposerJob
	 */
	private $entityIdDisposerJob;

	/**
	 * @var IteratorFactory
	 */
	private $iteratorFactory;

	/**
	 * @var CliMsgFormatter
	 */
	private $cliMsgFormatter;

	/**
	 * @since 3.1
	 *
	 * @param EntityIdDisposerJob $entityIdDisposerJob
	 * @param IteratorFactory $iteratorFactory
	 */
	public function __construct( EntityIdDisposerJob $entityIdDisposerJob, IteratorFactory $iteratorFactory ) {
		$this->entityIdDisposerJob = $entityIdDisposerJob;
		$this->iteratorFactory = $iteratorFactory;
		$this->cliMsgFormatter = new CliMsgFormatter();
	}

	/**
	 * @since 3.1
	 */
	public function run() {

		$this->messageReporter->reportMessage(
			"Removing outdated and invalid entities ...\n"
		);

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->firstCol( '   ... checking outdated entities ...' )
		);

		$resultIterator = $this->entityIdDisposerJob->newOutdatedEntitiesResultIterator();

		if ( ( $count = $resultIterator->count() ) > 0 ) {
			$this->disposeOutdatedEntities( $resultIterator, $count );
		} else {
			$this->messageReporter->reportMessage(
				$this->cliMsgFormatter->secondCol( CliMsgFormatter::OK )
			);
		}

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->firstCol( '   ... checking invalid entities by namespace ...' )
		);

		$resultIterator = $this->entityIdDisposerJob->newByNamespaceInvalidEntitiesResultIterator();

		if ( ( $count = $resultIterator->count() ) > 0 ) {
			$this->disposeOutdatedEntities( $resultIterator, $count );
		} else {
			$this->messageReporter->reportMessage(
				$this->cliMsgFormatter->secondCol( CliMsgFormatter::OK )
			);
		}

		$this->messageReporter->reportMessage( "   ... done.\n" );

		$this->messageReporter->reportMessage(
			"\nRemoving query links ...\n"
		);

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->firstCol( '   ... checking query links (invalid) ...' )
		);

		$resultIterator = $this->entityIdDisposerJob->newOutdatedQueryLinksResultIterator();

		if ( ( $count = $resultIterator->count() ) > 0 ) {
			$this->disposeOutdatedQueryLinks( $resultIterator, $count, 'query links (invalid)' );
		} else {
			$this->messageReporter->reportMessage(
				$this->cliMsgFormatter->secondCol( CliMsgFormatter::OK )
			);
		}

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->firstCol( '   ... checking query links (unassigned) ...' )
		);

		$resultIterator = $this->entityIdDisposerJob->newUnassignedQueryLinksResultIterator();

		if ( ( $count = $resultIterator->count() ) > 0 ) {
			$this->disposeOutdatedQueryLinks( $resultIterator, $count, 'query links (unassigned)' );
		} else {
			$this->messageReporter->reportMessage(
				$this->cliMsgFormatter->secondCol( CliMsgFormatter::OK )
			);
		}

		$this->messageReporter->reportMessage( "   ... done.\n" );
	}

	private function disposeOutdatedEntities( $resultIterator, $count ) {

		$this->messageReporter->reportMessage( "\n" );
		$chunkedIterator = $this->iteratorFactory->newChunkedIterator( $resultIterator, 200 );

		$counter = 0;

		foreach ( $chunkedIterator as $chunk ) {
			foreach ( $chunk as $row ) {
				$counter++;
				$msg = sprintf( "%s (%1.0f%%)", $row->smw_id, round( $counter / $count * 100 ) );

				$this->messageReporter->reportMessage(
					$this->cliMsgFormatter->twoColsOverride( '       ... cleaning up entity', $msg )
				);

				$this->entityIdDisposerJob->dispose( $row );
			}
		}

		$this->messageReporter->reportMessage( "\n" );

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->twoCols( '       ... removed (IDs) ...', $count )
		);
	}

	private function disposeOutdatedQueryLinks( $resultIterator, $count, $label ) {

		$this->messageReporter->reportMessage( "\n" );
		$counter = 0;

		foreach ( $resultIterator as $row ) {
			$counter++;
			$msg = sprintf( "%s (%1.0f%%)", $row->id, round( $counter / $count * 100 ) );

			$this->messageReporter->reportMessage(
				$this->cliMsgFormatter->twoColsOverride( "       ... cleaning up {$label}", $msg )
			);

			$this->entityIdDisposerJob->disposeQueryLinks( $row );
		}

		$this->messageReporter->reportMessage( "\n" );

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->twoCols( '       ... removed (IDs) ...', $count )
		);
	}

}
