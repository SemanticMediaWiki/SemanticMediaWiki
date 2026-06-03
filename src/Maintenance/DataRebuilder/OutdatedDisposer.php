<?php

namespace SMW\Maintenance\DataRebuilder;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\IteratorFactory;
use SMW\MediaWiki\Jobs\EntityIdDisposerJob;
use SMW\RequestOptions;
use SMW\SQLStore\PropertyTableIdReferenceDisposer;
use SMW\Utils\CliMsgFormatter;

/**
 * @private
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class OutdatedDisposer {

	use MessageReporterAwareTrait;

	private CliMsgFormatter $cliMsgFormatter;
	private int $shard = 0;
	private int $of = 1;

	/**
	 * @since 3.1
	 */
	public function __construct(
		private EntityIdDisposerJob $entityIdDisposerJob,
		private IteratorFactory $iteratorFactory,
	) {
		$this->cliMsgFormatter = new CliMsgFormatter();
	}

	/**
	 * @since 7.0.0
	 */
	public function setShard( int $shard, int $of ): void {
		$this->shard = $shard;
		$this->of = $of;
	}

	/**
	 * @since 3.1
	 */
	public function run(): void {
		$requestOptions = null;

		if ( $this->of > 1 ) {
			$requestOptions = new RequestOptions();
			$requestOptions->setOption( PropertyTableIdReferenceDisposer::OPT_SHARD_OF, $this->of );
			$requestOptions->setOption( PropertyTableIdReferenceDisposer::OPT_SHARD_INDEX, $this->shard );
		}

		$this->messageReporter->reportMessage(
			"Removing outdated and invalid entities ...\n"
		);

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->firstCol( '   ... checking outdated entities ...' )
		);

		$resultIterator = $this->entityIdDisposerJob->newOutdatedEntitiesResultIterator( $requestOptions );

		$count = $resultIterator->count();
		if ( $count > 0 ) {
			$this->disposeOutdatedEntities( $resultIterator, $count );
		} else {
			$this->messageReporter->reportMessage(
				$this->cliMsgFormatter->secondCol( CliMsgFormatter::OK )
			);
		}

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->firstCol( '   ... checking invalid entities by namespace ...' )
		);

		$resultIterator = $this->entityIdDisposerJob->newByNamespaceInvalidEntitiesResultIterator( $requestOptions );

		$count = $resultIterator->count();
		if ( $count > 0 ) {
			$this->disposeOutdatedEntities( $resultIterator, $count );
		} else {
			$this->messageReporter->reportMessage(
				$this->cliMsgFormatter->secondCol( CliMsgFormatter::OK )
			);
		}

		$this->messageReporter->reportMessage( "   ... done.\n" );

		if ( $this->shard === 0 ) {
			$this->messageReporter->reportMessage(
				"\nRemoving query links ...\n"
			);

			$this->messageReporter->reportMessage(
				$this->cliMsgFormatter->firstCol( '   ... checking query links (invalid) ...' )
			);

			$resultIterator = $this->entityIdDisposerJob->newOutdatedQueryLinksResultIterator();

			$count = $resultIterator->count();
			if ( $count > 0 ) {
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

			$count = $resultIterator->count();
			if ( $count > 0 ) {
				$this->disposeOutdatedQueryLinks( $resultIterator, $count, 'query links (unassigned)' );
			} else {
				$this->messageReporter->reportMessage(
					$this->cliMsgFormatter->secondCol( CliMsgFormatter::OK )
				);
			}

			$this->messageReporter->reportMessage( "   ... done.\n" );
		} else {
			$this->messageReporter->reportMessage(
				"\nRemoving query links ... (skipped on shard {$this->shard}; runs on shard 0)\n"
			);
		}
	}

	private function disposeOutdatedEntities( $resultIterator, int $count ): void {
		$this->messageReporter->reportMessage( "\n" );
		$chunkedIterator = $this->iteratorFactory->newChunkedIterator( $resultIterator, 200 );

		$counter = 0;

		foreach ( $chunkedIterator as $chunk ) {
			$rows = [];

			foreach ( $chunk as $row ) {
				$counter++;
				$rows[] = $row;
				$msg = sprintf( "%s (%1.0f%%)", $row->smw_id, round( $counter / $count * 100 ) );

				$this->messageReporter->reportMessage(
					$this->cliMsgFormatter->twoColsOverride( '       ... cleaning up entity', $msg )
				);
			}

			$this->entityIdDisposerJob->disposeList( $rows );
		}

		$this->messageReporter->reportMessage( "\n" );

		$this->messageReporter->reportMessage(
			$this->cliMsgFormatter->twoCols( '       ... removed (IDs) ...', $count )
		);
	}

	private function disposeOutdatedQueryLinks( $resultIterator, int $count, string $label ): void {
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
