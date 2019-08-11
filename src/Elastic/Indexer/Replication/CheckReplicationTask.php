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
class CheckReplicationTask extends Task {

	const CKEY_CHECK_REPLICATION_TASK = 'CheckReplicationTask';
	const TYPE_SUCCESS = 'success';

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var DocumentReplicationExaminer
	 */
	private $documentReplicationExaminer;

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
	 * @param DocumentReplicationExaminer $documentReplicationExaminer
	 * @param EntityCache $entityCache
	 */
	public function __construct( Store $store, DocumentReplicationExaminer $documentReplicationExaminer, EntityCache $entityCache ) {
		$this->store = $store;
		$this->documentReplicationExaminer = $documentReplicationExaminer;
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
		return $this->entityCache->fetch( $this->makeCacheKey( self::CKEY_CHECK_REPLICATION_TASK ) );
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 */
	public function deleteEntireReplicationTrail() {
		$this->entityCache->delete( $this->makeCacheKey( self::CKEY_CHECK_REPLICATION_TASK ) );
	}

	/**
	 * @since 3.1
	 *
	 * @param DIWikiPage|Title $title
	 */
	public function deleteReplicationTrail( $subject ) {

		if ( $subject instanceof \Title ) {
			$subject = DIWikiPage::newFromTitle( $subject );
		}

		if ( !$subject instanceof DIWikiPage ) {
			return;
		}

		$this->entityCache->deleteSub(
			$this->makeCacheKey( self::CKEY_CHECK_REPLICATION_TASK ),
			$this->makeCacheKey( $subject )
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

		$this->templateEngine = new TemplateEngine();
		$this->templateEngine->load( '/elastic/indexer/CheckReplicationTaskLine.ms', 'line_template' );

		$this->templateEngine->compile(
			'line_template',
			[
				'margin' => isset( $options['dir'] ) && $options['dir'] === 'rtl' ? 'right' : 'left'
			]
		);

		if ( ( $html = $this->canConnect() ) ) {
			return $this->wrapHTML( $html );
		}

		if ( ( $html = $this->inMaintenanceMode() ) ) {
			return $this->wrapHTML( $html );
		}

		return $this->check( $subject, $options );
	}

	private function check( $subject, $options ) {

		$result = $this->documentReplicationExaminer->check(
			$subject
		);

		$id = $subject->getId();
		$title = $subject->getTitle();
		$html = '';

		// Show a user readable representation especially when referring
		// to a predefined property
		if ( $subject->getNamespace() === SMW_NS_PROPERTY ) {
			$property = DIProperty::newFromUserLabel( $subject->getDBKey() );
			$title = $property->getDiWikiPage()->getTitle();
		}

		if ( $result !== [] && isset( $result['exception'] ) ) {
			$html = $this->exceptionErrorMsg( $result['exception'] );
		} elseif ( $result !== [] && isset( $result['modification_date_missing'] ) ) {
			$html = $this->replicationErrorMsg( $title->getPrefixedText(), $id );
		} elseif ( $result !== [] && isset( $result['modification_date_diff'] ) ) {
			$html = $this->replicationErrorMsg( $title->getPrefixedText(), $id, $result['modification_date_diff'] );
		} elseif ( $result !== [] && isset( $result['associated_revision_diff'] ) ) {
			$html = $this->replicationErrorMsg( $title->getPrefixedText(), $id, [], $result['associated_revision_diff'] );
		} elseif ( $subject->getNamespace() === NS_FILE ) {
			$html = $this->checkFileIngest( $subject );
		}

		$key = $this->makeCacheKey( $subject );
		$taskKey = $this->makeCacheKey( self::CKEY_CHECK_REPLICATION_TASK );

		// Only keep the cache around when ES has successful replicated the entity
		if ( $html === '' ) {
			$this->entityCache->save( $key, self::TYPE_SUCCESS, $this->cacheTTL );
			$this->entityCache->associate( $subject, $key );
			$this->entityCache->deleteSub( $taskKey, $key );
		} else {
			$this->entityCache->delete( $key );
			$this->entityCache->saveSub( $taskKey, $key, $subject->getHash() );
		}

		return $this->wrapHTML( $html );
	}

	private function exceptionErrorMsg( $e ) {

		$content = '';
		$this->errorTitle = 'smw-es-replication-error';

		$this->templateEngine->load( '/elastic/indexer/CheckReplicationTaskComment.ms', 'comment_template' );

		$this->templateEngine->compile(
			'comment_template',
			[
				'comment' => $this->msg( 'smw-es-replication-error-suggestions-exception' )
			]
		);

		if ( $e === 'BadRequest400Exception' ) {
			$content .= $this->msg( [ 'smw-es-replication-error-bad-request-exception', $e ] );
		} else {
			$content .= $this->msg( [ 'smw-es-replication-error-exception', $e->getMessage() ] );
		}

		$content .= $this->templateEngine->code( 'line_template' );
		$content .= $this->templateEngine->code( 'comment_template' );

		return $content;
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

	private function canConnect() {

		$connection = $this->store->getConnection( 'elastic' );
		$content = '';

		if ( $connection->ping() ) {
			return false;
		}

		$this->errorTitle = 'smw-es-replication-error';
		$this->templateEngine->load( '/elastic/indexer/CheckReplicationTaskComment.ms', 'comment_template' );

		$this->templateEngine->compile(
			'comment_template',
			[
				'comment' => $this->msg( 'smw-es-replication-error-suggestions-no-connection', Message::PARSE )
			]
		);

		$content .= $this->msg( [ 'smw-es-replication-error-no-connection' ], Message::PARSE );
		$content .= $this->templateEngine->code( 'line_template' );
		$content .= $this->templateEngine->code( 'comment_template' );

		return $content;
	}

	private function inMaintenanceMode() {

		$connection = $this->store->getConnection( 'elastic' );
		$content = '';

		if ( $connection->hasMaintenanceLock() === false ) {
			return false;
		}

		$this->errorTitle = 'smw-es-replication-error';
		$this->templateEngine->load( '/elastic/indexer/CheckReplicationTaskComment.ms', 'comment_template' );

		$this->templateEngine->compile(
			'comment_template',
			[
				'comment' => $this->msg( 'smw-es-replication-error-suggestions-maintenance-mode', Message::PARSE )
			]
		);

		$content .= $this->msg( [ 'smw-es-replication-error-maintenance-mode' ], Message::PARSE );
		$content .= $this->templateEngine->code( 'line_template' );
		$content .= $this->templateEngine->code( 'comment_template' );

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
