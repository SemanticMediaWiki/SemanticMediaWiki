<?php

namespace SMW\Elastic\Hooks;

use Onoi\MessageReporter\MessageReporterAwareTrait;
use SMW\Store;
use SMW\Elastic\Indexer\Rebuilder\Rebuilder;
use SMW\Utils\CliMsgFormatter;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class UpdateEntityCollationComplete {

	use MessageReporterAwareTrait;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var integer
	 */
	private $countDown = 5;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
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

		$cliMsgFormatter = new CliMsgFormatter();

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->section( 'Elasticsearch update (AfterUpdateEntityCollationComplete)' )
		);

		$rebuilder->setMessageReporter(
			$this->messageReporter
		);

		if ( !$rebuilder->ping() ) {
			return $this->messageReporter->reportMessage(
				"\nElasticsearch endpoint(s) are not available!\n"
			);
		}

		$text = [
			"The entity collation was updated which requires to rebuild",
			"the Elasticsearch indicies as well to reflect those changes",
			"therefore a rebuild is planned to run shortly."
		];

		$this->messageReporter->reportMessage(
			"\n" . $cliMsgFormatter->wordwrap( $text ) . "\n"
		);

		$this->messageReporter->reportMessage(
			$cliMsgFormatter->countDown(
				"Abort the rebuild with CTRL-C in ...",
				$this->countDown
			)
		);

		$this->messageReporter->reportMessage(
			"\nRunning indices rebuild ...\n"
		);

		if ( !$rebuilder->hasIndices() ) {

			$this->messageReporter->reportMessage(
				$cliMsgFormatter->firstCol( '   ... creating required indices and aliases ...' )
			);

			$rebuilder->createIndices();

			$this->messageReporter->reportMessage(
				$cliMsgFormatter->secondCol( CliMsgFormatter::OK )
			);
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

		if ( $res->numRows() == 0 ) {
			$this->messageReporter->reportMessage( '   ... no documents to process ...' );
		}

		$this->rebuild( $rebuilder, $res, $last );

		$this->messageReporter->reportMessage( "   ... done.\n" );

		return true;
	}

	private function rebuild( $rebuilder, $res, $last ) {

		$cliMsgFormatter = new CliMsgFormatter();

		$rebuilder->set( 'skip-fileindex', true );

		$i = 0;
		$last = $res->numRows();
		$entityIdManager = $this->store->getObjectIds();

		foreach ( $res as $row ) {
			$i++;
			$progress = $cliMsgFormatter->progressCompact( $i, $last, $i, $last );

			$this->messageReporter->reportMessage(
				$cliMsgFormatter->twoColsOverride( '... updating document ...', $progress, 3 )
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

		$this->messageReporter->reportMessage( "\n   ... done.\n" );
		$this->messageReporter->reportMessage( "\nSettings and mappings ..." );

		$rebuilder->setDefaults();
		$rebuilder->refresh();
	}

}
