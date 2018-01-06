<?php

namespace SMW\Maintenance;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\Store;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\PropertyTableIdReferenceDisposer;

/**
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class DuplicateEntitiesDisposer {

	use MessageReporterAwareTrait;

	/**
	 * @var Store
	 */
	private $store = null;

	/**
	 * @since 3.0
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.0
	 */
	public function findDuplicateEntityRecords() {
		return $this->store->getObjectIds()->findDuplicateEntityRecords();
	}

	/**
	 * @since 3.0
	 *
	 * @param array $duplicateEntityRecords
	 */
	public function verifyAndDispose( array $duplicateEntityRecords ) {

		$count = count( $duplicateEntityRecords );
		$this->messageReporter->reportMessage( "Found: $count duplicates\n" );

		if ( $count > 0 ) {
			$this->doDispose( $duplicateEntityRecords );
		}
	}

	private function doDispose( array $duplicateEntityRecords ) {

		$propertyTableIdReferenceDisposer = new PropertyTableIdReferenceDisposer(
			$this->store
		);

		$propertyTableIdReferenceDisposer->setRedirectRemoval( true );
		$connection = $this->store->getConnection( 'mw.db' );

		$log = [
			'disposed' => [],
			'untouched' => []
		];

		$i = 0;
		foreach ( $duplicateEntityRecords as $entityRecord ) {
			unset( $entityRecord['count'] );

			if ( ( $i ) % 60 === 0 ) {
				$this->messageReporter->reportMessage( "\n" );
			}

			$this->messageReporter->reportMessage( '.' );

			$res = $connection->select(
				SQLStore::ID_TABLE,
				[
					'smw_id',
				],
				[
					'smw_title'=> $entityRecord['smw_title'],
					'smw_namespace'=> $entityRecord['smw_namespace'],
					'smw_iw'=> $entityRecord['smw_iw'],
					'smw_subobject'=> $entityRecord['smw_subobject']
				],
				__METHOD__
			);

			foreach ( $res as $row ) {
				if ( $propertyTableIdReferenceDisposer->isDisposable( $row->smw_id ) ) {
					$propertyTableIdReferenceDisposer->cleanUpTableEntriesById( $row->smw_id );
					$log['disposed'][$row->smw_id] = $entityRecord;
				} else {
					$log['untouched'][$row->smw_id] = $entityRecord;
				}
			}

			$i++;
		}

		$this->messageReporter->reportMessage(
			"\n\nLog\n\n" . json_encode( $log, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n"
		);
	}

}
