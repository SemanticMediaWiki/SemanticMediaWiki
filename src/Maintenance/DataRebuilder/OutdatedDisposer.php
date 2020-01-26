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
			"Removing outdated entities and query links ...\n"
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
			$this->cliMsgFormatter->firstCol( '... checking query links (unassigned) ...', 3 )
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
		$i = 0;

		foreach ( $chunkedIterator as $chunk ) {
			foreach ( $chunk as $row ) {
				$progress = $this->cliMsgFormatter->progressCompact( ++$i, $count );

				$this->messageReporter->reportMessage(
					$this->cliMsgFormatter->twoColsOverride( '... cleaning up entity ...', $progress, 7 )
				);

				$this->entityIdDisposerJob->dispose( $row );
			}
		}

		$this->messageReporter->reportMessage( "\n" );

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->twoCols( '... removed (IDs) ...', $count, 7 )
		);
	}

	private function disposeOutdatedQueryLinks( $resultIterator, $count, $label ) {

		$this->messageReporter->reportMessage( "\n" );
		$i = 0;

		foreach ( $resultIterator as $row ) {
			$progress = $this->cliMsgFormatter->progressCompact( ++$i, $count );

			$this->messageReporter->reportMessage(
				$this->cliMsgFormatter->twoColsOverride( "... cleaning up {$label} ...", $progress, 7 )
			);

			$this->entityIdDisposerJob->disposeQueryLinks( $row );
		}

		$this->messageReporter->reportMessage( "\n" );

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->twoCols( '... removed (IDs) ...', $count, 7 )
		);
	}

}
