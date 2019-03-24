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
use Title;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class CheckReplicationTask extends Task {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var ReplicationStatus
	 */
	private $replicationStatus;

	/**
	 * @var EntityCache
	 */
	private $entityCache;

	/**
	 * @var TemplateEngine
	 */
	private $templateEngine;

	/**
	 * @var boolean
	 */
	private $errorTitle = '';

	/**
	 * @var integer
	 */
	private $cacheTTL = 3600;

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 * @param ReplicationStatus $replicationStatus
	 * @param EntityCache $entityCache
	 */
	public function __construct( Store $store, ReplicationStatus $replicationStatus, EntityCache $entityCache ) {
		$this->store = $store;
		$this->replicationStatus = $replicationStatus;
		$this->entityCache = $entityCache;
	}

	/**
	 * @since 3.1
	 *
	 * @param DIWikiPage $subject
	 *
	 * @return string
	 */
	public static function makeCacheKey( $subject ) {

		if ( $subject instanceof DIWikiPage ) {
			$subject = $subject->getHash();
		}

		return EntityCache::makeCacheKey( 'es-replication-check', $subject );
	}

	/**
	 * @since 3.1
	 *
	 * @return []
	 */
	public function getReplicationFailures() {
		return $this->entityCache->fetch( $this->makeCacheKey( 'CheckReplicationTask' ) );
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 */
	public function deleteReplicationTrail( Title $title ) {
		$this->entityCache->deleteSub(
			$this->makeCacheKey( 'CheckReplicationTask' ),
			$this->makeCacheKey( DIWikiPage::newFromTitle( $title ) )
		);
	}

	/**
	 * @since 3.1
	 *
	 * @param integer $cacheTTL
	 */
	public function setCacheTTL( $cacheTTL ) {
		$this->cacheTTL = $cacheTTL > 0 ? $cacheTTL : 3600;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function process( array $parameters ) {

		if ( $parameters['subject'] === '' ) {
			return [ 'done' => false ];
		}

		$subject = DIWikiPage::doUnserialize(
			$parameters['subject']
		);

		$html = $this->checkReplication(
			$subject,
			$parameters
		);

		return [ 'done' => true, 'html' => $html ];
	}

	/**
	 * @since 3.1
	 *
	 * @param DIWikiPage $subject
	 * @param array $options
	 *
	 * @return string
	 */
	public function checkReplication( DIWikiPage $subject, array $options = [] ) {

		$html = '';

		$this->templateEngine = new TemplateEngine();
		$this->templateEngine->load( '/elastic/indexer/CheckReplicationTaskLine.ms', 'line_template' );

		$this->templateEngine->compile(
			'line_template',
			[
				'margin' => isset( $options['dir'] ) && $options['dir'] === 'rtl' ? 'right' : 'left'
			]
		);

		$id = $this->store->getObjectIds()->getID( $subject );

		$rev_store = $this->store->getObjectIds()->findAssociatedRev(
			$subject->getDBKey(),
			$subject->getNamespace(),
			$subject->getInterwiki()
		);

		$title = $subject->getTitle();

		// Find the time from elastic
		$dataItem = $this->replicationStatus->getModificationDate(
			$id
		);

		// What is stored in the DB
		$pv = $this->store->getPropertyValues(
			$subject,
			new DIProperty( '_MDAT' )
		);

		if ( $dataItem === false || $pv === [] ) {
			$html = $this->replicationErrorMsg( $title->getPrefixedText(), $id );
		} elseif ( !end( $pv )->equals( $dataItem ) ) {
			$dates = [
				'time_es' => $dataItem->asDateTime()->format( 'Y-m-d H:i:s' ),
				'time_store' => end( $pv )->asDateTime()->format( 'Y-m-d H:i:s' )
			];
			$html = $this->replicationErrorMsg( $title->getPrefixedText(), $id, $dates );
		} elseif ( ( $rev_es = $this->replicationStatus->getAssociatedRev( $id ) ) != $rev_store ) {
			$revs = [
				'rev_es' => $rev_es,
				'rev_store' => $rev_store
			];
			$html = $this->replicationErrorMsg( $title->getPrefixedText(), $id, [], $revs );
		} elseif ( $subject->getNamespace() === NS_FILE ) {
			$html = $this->checkFileIngest( $subject );
		}

		$key = $this->makeCacheKey( $subject );

		// Only keep the cache around when ES has successful replicated the entity
		if ( $html === '' ) {
			$this->entityCache->save( $key, 'success', $this->cacheTTL );
			$this->entityCache->associate( $subject, $key );
			$this->entityCache->deleteSub( $this->makeCacheKey( 'CheckReplicationTask' ), $key );
		} else {
			$this->entityCache->delete( $key );
			$this->entityCache->saveSub( $this->makeCacheKey( 'CheckReplicationTask' ), $key, $subject->getHash() );
		}

		return $this->wrapHTML( $html );
	}

	private function replicationErrorMsg( $title_text, $id, $dates = [], $revs = [] ) {

		$content = '';
		$this->errorTitle = 'smw-es-replication-error';

		$this->templateEngine->load( '/elastic/indexer/CheckReplicationTaskComment.ms', 'comment_template' );

		$this->templateEngine->compile(
			'comment_template',
			[
				'comment' => $this->msg( 'smw-es-replication-error-suggestions' )
			]
		);

		if ( $dates !== [] ) {
			$content .= $this->msg( [ 'smw-es-replication-error-divergent-date', $title_text, $id ] );
			$content .= $this->templateEngine->code( 'line_template' );
			$content .= $this->msg( [ 'smw-es-replication-error-divergent-date-detail', $dates['time_es'], $dates['time_store'] ], Message::PARSE );
			$content .= $this->templateEngine->code( 'line_template' );
			$content .= $this->templateEngine->code( 'comment_template' );
		} elseif ( $revs !== [] ) {
			$content .= $this->msg( [ 'smw-es-replication-error-divergent-revision', $title_text, $id ] );
			$content .= $this->templateEngine->code( 'line_template' );
			$content .= $this->msg( [ 'smw-es-replication-error-divergent-revision-detail', $revs['rev_es'], $revs['rev_store'] ], Message::PARSE );
			$content .= $this->templateEngine->code( 'line_template' );
			$content .= $this->templateEngine->code( 'comment_template' );
		} else {
			$content .= $this->msg( [ 'smw-es-replication-error-missing-id', $title_text, $id ] );
			$content .= $this->templateEngine->code( 'line_template' );
			$content .= $this->templateEngine->code( 'comment_template' );
		}

		return $content;
	}

	private function checkFileIngest( $subject ) {

		$config = $this->store->getConnection( 'elastic' )->getConfig();
		$content = '';

		$this->errorTitle = 'smw-es-replication-file-ingest-error';

		if ( $config->dotGet( 'indexer.experimental.file.ingest', false ) === false ) {
			return '';
		}

		$pv = $this->store->getPropertyValues(
			$subject,
			new DIProperty( '_FILE_ATTCH' )
		);

		if ( $pv === [] ) {

			$this->templateEngine->load( '/elastic/indexer/CheckReplicationTaskComment.ms', 'comment_template_ingest' );

			$this->templateEngine->compile(
				'comment_template_ingest',
				[
					'comment' => $this->msg( 'smw-es-replication-error-file-ingest-missing-file-attachment-suggestions', Message::PARSE )
				]
			);

			$title = $subject->getTitle();
			$content .= $this->msg( [ 'smw-es-replication-error-file-ingest-missing-file-attachment', $title->getPrefixedText() ], Message::PARSE );
			$content .= $this->templateEngine->code( 'line_template' );
			$content .= $this->templateEngine->code( 'comment_template_ingest' );
		};

		return $content;
	}

	private function wrapHTML( $content ) {

		if ( $content === '' ) {
			return '';
		}

		$this->templateEngine->load( '/elastic/indexer/CheckReplicationTaskHighlighter.ms', 'highlighter_template' );

		$this->templateEngine->compile(
			'highlighter_template',
			[
				'title' => $this->msg( $this->errorTitle ),
				'content' => htmlspecialchars( $content, ENT_QUOTES )
			]
		);

		return $this->templateEngine->code( 'highlighter_template' );
	}

	private function msg( $key, $type = Message::TEXT, $lang = Message::USER_LANGUAGE ) {
		return Message::get( $key, $type, $lang );
	}

}
