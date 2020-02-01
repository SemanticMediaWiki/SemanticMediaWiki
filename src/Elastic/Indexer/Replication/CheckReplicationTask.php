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

	const REPLICATION_CHECK_TASK_CACKE_KEY = 'CheckReplicationTask';
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
		return $this->entityCache->fetch( $this->makeCacheKey( self::REPLICATION_CHECK_TASK_CACKE_KEY ) );
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 */
	public function deleteEntireReplicationTrail() {
		$this->entityCache->delete( $this->makeCacheKey( self::REPLICATION_CHECK_TASK_CACKE_KEY ) );
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
			$this->makeCacheKey( self::REPLICATION_CHECK_TASK_CACKE_KEY ),
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

		$connection = $this->store->getConnection( 'elastic' );

		if ( $connection->ping() === false ) {
			return $this->wrapHTML( $this->connectionError() );
		} elseif ( $connection->hasMaintenanceLock() ) {
			return $this->wrapHTML( $this->maintenanceError() );
		}

		return $this->check( $subject, $options );
	}

	private function check( $subject, $options ) {

		$error = $this->documentReplicationExaminer->check(
			$subject,
			[
				DocumentReplicationExaminer::CHECK_MISSING_FILE_ATTACHMENT => true
			]
		);

		$title = $subject->getTitle();
		$html = '';

		// Show a user readable representation especially when referring
		// to a predefined property
		if ( $subject->getNamespace() === SMW_NS_PROPERTY ) {
			$property = DIProperty::newFromUserLabel( $subject->getDBKey() );
			$title = $property->getDiWikiPage()->getTitle();
		}

		if ( $error instanceof ReplicationError ) {
			$html = $this->buildHTML( $error, $title->getPrefixedText() );
		}

		$key = $this->makeCacheKey( $subject );
		$taskKey = $this->makeCacheKey( self::REPLICATION_CHECK_TASK_CACKE_KEY );

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

	private function buildHTML( ReplicationError $error, $title_text ) {

		$this->errorTitle = 'smw-es-replication-error';

		$this->templateEngine->load( '/elastic/indexer/CheckReplicationTaskComment.ms', 'comment_template' );
		$this->templateEngine->load( '/elastic/indexer/checkreplication.compare.list.ms', 'compare_template' );

		if ( $error->is( ReplicationError::TYPE_EXCEPTION ) ) {
			$html = $this->exceptionError( $error );
		} elseif ( $error->is( ReplicationError::TYPE_MODIFICATION_DATE_DIFF ) ) {
			$html = $this->modificationDateDiffError( $error, $title_text );
		} elseif ( $error->is( ReplicationError::TYPE_ASSOCIATED_REVISION_DIFF ) ) {
			$html = $this->associatedRevisionDiffError( $error, $title_text );
		} elseif ( $error->is( ReplicationError::TYPE_FILE_ATTACHMENT_MISSING ) ) {
			$html = $this->fileAttachmentError( $error, $title_text );
		} else {
			$html = $this->missingDocumentError( $error, $title_text );
		}

		return $html;
	}

	private function connectionError() {

		$html = '';

		$this->errorTitle = 'smw-es-replication-error';
		$this->templateEngine->load( '/elastic/indexer/CheckReplicationTaskComment.ms', 'comment_template' );

		$this->templateEngine->compile(
			'comment_template',
			[
				'comment' => $this->msg( 'smw-es-replication-error-suggestions-no-connection', Message::PARSE )
			]
		);

		$html .= $this->msg( [ 'smw-es-replication-error-no-connection' ], Message::PARSE );
		$html .= $this->templateEngine->code( 'line_template' );
		$html .= $this->templateEngine->code( 'comment_template' );

		return $html;
	}

	private function maintenanceError() {

		$html = '';

		$this->errorTitle = 'smw-es-replication-error';
		$this->templateEngine->load( '/elastic/indexer/CheckReplicationTaskComment.ms', 'comment_template' );

		$this->templateEngine->compile(
			'comment_template',
			[
				'comment' => $this->msg( 'smw-es-replication-error-suggestions-maintenance-mode', Message::PARSE )
			]
		);

		$html .= $this->msg( [ 'smw-es-replication-error-maintenance-mode' ], Message::PARSE );
		$html .= $this->templateEngine->code( 'line_template' );
		$html .= $this->templateEngine->code( 'comment_template' );

		return $html;
	}

	private function exceptionError( ReplicationError $error ) {

		$html = '';

		$this->templateEngine->compile(
			'comment_template',
			[
				'comment' => $this->msg( 'smw-es-replication-error-suggestions-exception' )
			]
		);

		if ( $error->get( 'exception_error' ) === 'BadRequest400Exception' ) {
			$html .= $this->msg( [ 'smw-es-replication-error-bad-request-exception', $error->get( 'exception_error' ) ], Message::PARSE );
		} else {
			$html .= $this->msg( [ 'smw-es-replication-error-exception', $error->get( 'exception_error' ) ] );
		}

		$html .= $this->templateEngine->code( 'line_template' );
		$html .= $this->templateEngine->code( 'comment_template' );

		return $html;
	}

	private function modificationDateDiffError( ReplicationError $error, $title_text ) {

		$html = '';

		$this->templateEngine->compile(
			'comment_template',
			[
				'comment' => $this->msg( 'smw-es-replication-error-suggestions' )
			]
		);

		$this->templateEngine->compile(
			'compare_template',
			[
				'explain' => $this->msg( 'smw-es-replication-error-divergent-date-short' ),
				'es_key' => 'Elasticsearch',
				'es_value' => $error->get( 'time_es' ),
				'backend_key' => 'Database',
				'backend_value' => $error->get( 'time_store' )
			]
		);

		$html .= $this->msg( [ 'smw-es-replication-error-divergent-date', $title_text, $error->get( 'id' ) ] );
		$html .= $this->templateEngine->code( 'line_template' );
		$html .= $this->templateEngine->code( 'compare_template' );
		$html .= $this->templateEngine->code( 'line_template' );
		$html .= $this->templateEngine->code( 'comment_template' );

		return $html;
	}

	private function associatedRevisionDiffError( ReplicationError $error, $title_text ) {

		$html = '';

		$this->templateEngine->compile(
			'comment_template',
			[
				'comment' => $this->msg( 'smw-es-replication-error-suggestions' )
			]
		);

		$this->templateEngine->compile(
			'compare_template',
			[
				'explain' => $this->msg( 'smw-es-replication-error-divergent-revision-short' ),
				'es_key' => 'Elasticsearch',
				'es_value' => $error->get( 'rev_es' ),
				'backend_key' => 'Database',
				'backend_value' => $error->get( 'rev_store' )
			]
		);

		$html .= $this->msg( [ 'smw-es-replication-error-divergent-revision', $title_text, $error->get( 'id' ) ] );
		$html .= $this->templateEngine->code( 'line_template' );
		$html .= $this->templateEngine->code( 'compare_template' );
		$html .= $this->templateEngine->code( 'line_template' );
		$html .= $this->templateEngine->code( 'comment_template' );

		return $html;
	}

	private function missingDocumentError( ReplicationError $error, $title_text ) {

		$html = '';

		$this->templateEngine->compile(
			'comment_template',
			[
				'comment' => $this->msg( 'smw-es-replication-error-suggestions' )
			]
		);

		$html .= $this->msg( [ 'smw-es-replication-error-missing-id', $title_text, $error->get( 'id' ) ] );
		$html .= $this->templateEngine->code( 'line_template' );
		$html .= $this->templateEngine->code( 'comment_template' );

		return $html;
	}

	private function fileAttachmentError( ReplicationError $error, $title_text ) {

		$html = '';

		$this->templateEngine->load( '/elastic/indexer/CheckReplicationTaskComment.ms', 'comment_template_ingest' );

		$this->templateEngine->compile(
			'comment_template_ingest',
			[
				'comment' => $this->msg( 'smw-es-replication-error-file-ingest-missing-file-attachment-suggestions', Message::PARSE )
			]
		);

		$html .= $this->msg( [ 'smw-es-replication-error-file-ingest-missing-file-attachment', $title_text ], Message::PARSE );
		$html .= $this->templateEngine->code( 'line_template' );
		$html .= $this->templateEngine->code( 'comment_template_ingest' );

		return $html;
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
