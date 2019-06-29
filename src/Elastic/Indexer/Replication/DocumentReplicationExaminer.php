<?php

namespace SMW\Elastic\Indexer\Replication;

use Onoi\Cache\Cache;
use SMW\Store;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\MediaWiki\Api\Tasks\Task;
use SMW\Message;
use SMW\EntityCache;
use Html;
use SMW\Utils\TemplateEngine;
use SMW\Elastic\Connection\Client as ElasticClient;
use Title;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class DocumentReplicationExaminer {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 * @param ReplicationStatus $replicationStatus
	 */
	public function __construct( Store $store, ReplicationStatus $replicationStatus ) {
		$this->store = $store;
		$this->replicationStatus = $replicationStatus;
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $id
	 *
	 * @return boolean
	 */
	public function documentExistsById( $id ) {
		return $this->replicationStatus->get( 'documentExistsById', $id );
	}

	/**
	 * @since 3.1
	 *
	 * @param DIWikiPage $subject
	 *
	 * @return []
	 */
	public function check( DIWikiPage $subject ) {

		$idTable = $this->store->getObjectIds();
		$exceptionError = null;

		$id = $idTable->getSMWPageID(
			$subject->getDBKey(),
			$subject->getNamespace(),
			$subject->getInterwiki(),
			$subject->getSubobjectname(),
			'',
			true
		);

		$subject->setId( $id );
		$rev_store = $idTable->findAssociatedRev( $id );

		try {
			$replicationStatus = $this->replicationStatus->get( 'modification_date_associated_revision', $id );
		} catch ( \Elasticsearch\Common\Exceptions\BadRequest400Exception $e ) {
			$exceptionError = 'BadRequest400Exception';
		}

		if ( $exceptionError !== null ) {
			return [ 'exception' => $exceptionError ];
		}

		// What is stored in the DB
		$pv = $this->store->getPropertyValues(
			$subject,
			new DIProperty( '_MDAT' )
		);

		if ( $replicationStatus['modification_date'] === false || $pv === [] ) {
			return [ 'modification_date_missing' => $id ];
		} elseif ( !end( $pv )->equals( $replicationStatus['modification_date'] ) ) {
			$dates = [
				'time_es' => $replicationStatus['modification_date']->asDateTime()->format( 'Y-m-d H:i:s' ),
				'time_store' => end( $pv )->asDateTime()->format( 'Y-m-d H:i:s' )
			];
			return [ 'modification_date_diff' => $dates ];
		} elseif ( $replicationStatus['associated_revision'] != $rev_store ) {
			$revs = [
				'rev_es' => $replicationStatus['associated_revision'],
				'rev_store' => $rev_store
			];
			return [ 'associated_revision_diff' => $revs ];
		}

		return [];
	}

}
