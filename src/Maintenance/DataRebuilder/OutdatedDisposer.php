<?php

namespace SMW\Maintenance\DataRebuilder;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\MediaWiki\Jobs\EntityIdDisposerJob;
use SMW\IteratorFactory;
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
	 * @since 3.1
	 *
	 * @param EntityIdDisposerJob $entityIdDisposerJob
	 * @param IteratorFactory $iteratorFactory
	 */
	public function __construct( EntityIdDisposerJob $entityIdDisposerJob, IteratorFactory $iteratorFactory ) {
		$this->entityIdDisposerJob = $entityIdDisposerJob;
		$this->iteratorFactory = $iteratorFactory;
	}

	/**
	 * @since 3.1
	 */
	public function run() {

		$this->messageReporter->reportMessage( "Removing outdated entities and query links ..." );
		$this->messageReporter->reportMessage( "\n   ... checking entities ..." );

		$resultIterator = $this->entityIdDisposerJob->newOutdatedEntitiesResultIterator();

		if ( ( $count = $resultIterator->count() ) > 0 ) {
			$this->disposeOutdatedEntities( $resultIterator, $count );
		}

		$this->messageReporter->reportMessage( "\n   ... done." );
		$this->messageReporter->reportMessage( "\n   ... checking query links ..." );

		$resultIterator = $this->entityIdDisposerJob->newOutdatedQueryLinksResultIterator();

		if ( ( $count = $resultIterator->count() ) > 0 ) {
			$this->disposeOutdatedQueryLinks( $resultIterator, $count, 'query links (invalid)' );
		}

		$resultIterator = $this->entityIdDisposerJob->newUnassignedQueryLinksResultIterator();

		if ( ( $count = $resultIterator->count() ) > 0 ) {
			$this->disposeOutdatedQueryLinks( $resultIterator, $count, 'query links (unassigned)' );
		}

		$this->messageReporter->reportMessage( "\n   ... done.\n" );
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
					"\r". sprintf( "%-50s%s", "       ... cleaning up entity", $msg )
				);

				$this->entityIdDisposerJob->dispose( $row );
			}
		}

		$this->messageReporter->reportMessage( "\n       ... {$count} IDs removed ..." );
	}

	private function disposeOutdatedQueryLinks( $resultIterator, $count, $label ) {

		$this->messageReporter->reportMessage( "\n" );
		$counter = 0;

		foreach ( $resultIterator as $row ) {
			$counter++;
			$msg = sprintf( "%s (%1.0f%%)", $row->id, round( $counter / $count * 100 ) );

			$this->messageReporter->reportMessage(
				"\r". sprintf( "%-50s%s", "       ... cleaning up {$label}", $msg )
			);

			$this->entityIdDisposerJob->disposeQueryLinks( $row );
		}

		$this->messageReporter->reportMessage( "\n       ... {$count} IDs removed ..." );
	}

}
