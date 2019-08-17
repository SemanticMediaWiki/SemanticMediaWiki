<?php

namespace SMW\Elastic\Hooks;

use Onoi\MessageReporter\MessageReporter;
use SMW\Store;
use SMW\Elastic\Indexer\Rebuilder;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class UpdateEntityCollationComplete {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var MessageReporter
	 */
	private $messageReporter;

	/**
	 * @var integer
	 */
	private $countDown = 5;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 * @param MessageReporter $messageReporter
	 */
	public function __construct( Store $store, MessageReporter $messageReporter ) {
		$this->store = $store;
		$this->messageReporter = $messageReporter;
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $countDown
	 */
	public function setCountDown( $countDown ) {
		$this->countDown = $countDown;
	}

	/**
	 * @since 3.1
	 *
	 * @param Rebuilder $rebuilder
	 */
	public function runUpdate( Rebuilder $rebuilder ) {

		$this->messageReporter->reportMessage(
			"\nThe entity collation was updated which requires to rebuild\n" .
			"the Elasticsearch indicies as well to reflect those changes\n" .
			"therefore a rebuild is planned to run shortly.\n"
		);

		if ( $this->countDown > 0 ) {
			$this->showCountDown();
		}

		$this->messageReporter->reportMessage(
			"\nRunning an index rebuild ..."
		);

		$rebuilder->setMessageReporter(
			$this->messageReporter
		);

		if ( !$rebuilder->ping() ) {
			return $this->messageReporter->reportMessage(
				"\nElasticsearch endpoint(s) are not available!\n"
			);
		}

		if ( !$rebuilder->hasIndices() ) {
			$this->messageReporter->reportMessage( "\n   ... creating required indices and aliases ..." );
			$rebuilder->createIndices();
		}

		$connection = $this->store->getConnection( 'mw.db' );

		$conditions = [
			"smw_iw!=" . $connection->addQuotes( SMW_SQL3_SMWIW_OUTDATED )
		];

		$rebuilder->prepare();

		list( $res, $last ) = $rebuilder->select(
			$this->store,
			$conditions
		);

		if ( $res->numRows() > 0 ) {
			$this->messageReporter->reportMessage( "\n" );
		} else {
			$this->messageReporter->reportMessage( "\n" . '   ... no documents to process ...' );
		}

		$this->rebuild( $rebuilder, $res, $last );

		$this->messageReporter->reportMessage( "\n   ... done.\n" );

		return true;
	}

	private function rebuild( $rebuilder, $res, $last ) {

		$rebuilder->set( 'skip-fileindex', true );

		$i = 0;
		$last = $res->numRows();
		$entityIdManager = $this->store->getObjectIds();

		foreach ( $res as $row ) {
			$i++;

			$this->messageReporter->reportMessage(
				"\r". sprintf( "%-50s%s", "   ... updating document", sprintf( "%4.0f%% (%s/%s)", ( $i / $last ) * 100, $i, $last ) )
			);

			if ( $row->smw_iw === SMW_SQL3_SMWDELETEIW || $row->smw_iw === SMW_SQL3_SMWREDIIW ) {
				$rebuilder->delete( $row->smw_id );
				continue;
			}

			$dataItem = $entityIdManager->getDataItemById(
				$row->smw_id
			);

			if ( $dataItem === null ) {
				continue;
			}

			$semanticData = $this->store->getSemanticData( $dataItem );
			$semanticData->setExtensionData( 'revision_id', $row->smw_rev );

			$rebuilder->rebuild( $row->smw_id, $semanticData );
		}

		$rebuilder->setDefaults();
		$rebuilder->refresh();
	}

	private function showCountDown() {

		$this->messageReporter->reportMessage(
			"\nAbort the rebuild with control-c in the next five seconds ...  "
		);

		swfCountDown( $this->countDown );
	}

}
