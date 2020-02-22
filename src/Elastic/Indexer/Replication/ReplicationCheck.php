<?php

namespace SMW\Elastic\Indexer\Replication;

use Onoi\Cache\Cache;
use SMW\Store;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Message;
use SMW\EntityCache;
use Html;
use SMW\Utils\TemplateEngine;
use SMW\Elastic\Connection\Client as ElasticClient;
use SMW\Localizer\MessageLocalizerTrait;
use Title;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ReplicationCheck {

	use MessageLocalizerTrait;

	const REPLICATION_CHECK_TASK_CACKE_KEY = 'CheckReplicationTask';
	const TYPE_SUCCESS = 'success';

	const SEVERITY_TYPE_ERROR = 'error';
	const SEVERITY_TYPE_WARNING = 'warning';

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
	 * @var string
	 */
	private $languageCode = '';

	/**
	 * @var string
	 */
	private $severityType = self::SEVERITY_TYPE_ERROR;

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
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getErrorTitle() : string {
		return $this->errorTitle;
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getSeverityType() : string {
		return $this->severityType;
	}

	/**
	 * @since 3.1
	 *
	 * @param array $parameters
	 *
	 * @return array
	 */
	public function process( array $parameters ) {

		if ( !isset( $parameters['subject'] ) || $parameters['subject'] === '' ) {
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
		$this->templateEngine->bulkLoad(
			[
				'/elastic/indexer/line.ms' => 'line_template',
				'/elastic/indexer/compare.list.ms' => 'compare_template',
				'/elastic/indexer/text.ms' => 'text_template',
				'/indicator/comment.ms' => 'comment_template',
			]
		);

		$this->languageCode = $options['uselang'] ?? Message::USER_LANGUAGE;

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

		$this->templateEngine->compile(
			'text_template',
			[
				'error_code' => 'smw-es-replication-error-no-connection',
				'text' => $this->msg( [ 'smw-es-replication-error-no-connection' ], Message::PARSE, $this->languageCode )
			]
		);

		$this->templateEngine->compile(
			'comment_template',
			[
				'comment' => $this->msg( 'smw-es-replication-error-suggestions-no-connection', Message::PARSE, $this->languageCode )
			]
		);

		$html .= $this->templateEngine->code( 'text_template' );
		$html .= $this->templateEngine->code( 'line_template' );
		$html .= $this->templateEngine->code( 'comment_template' );

		return $html;
	}

	private function maintenanceError() {

		$html = '';

		$this->errorTitle = 'smw-es-replication-error';

		$this->templateEngine->compile(
			'text_template',
			[
				'error_code' => 'smw-es-replication-error-maintenance-mode',
				'text' => $this->msg( [ 'smw-es-replication-error-maintenance-mode' ], Message::PARSE, $this->languageCode )
			]
		);

		$this->templateEngine->compile(
			'comment_template',
			[
				'comment' => $this->msg( 'smw-es-replication-error-suggestions-maintenance-mode', Message::PARSE, $this->languageCode )
			]
		);

		$html .= $this->templateEngine->code( 'text_template' );
		$html .= $this->templateEngine->code( 'line_template' );
		$html .= $this->templateEngine->code( 'comment_template' );

		return $html;
	}

	private function exceptionError( ReplicationError $error ) {

		$html = '';

		if ( $error->get( 'exception_error' ) === 'BadRequest400Exception' ) {
			$text = $this->msg( [ 'smw-es-replication-error-bad-request-exception', $error->get( 'exception_error' ) ], Message::PARSE, $this->languageCode );
			$error_code = 'smw-es-replication-error-bad-request-exception';
		} else {
			$text = $this->msg( [ 'smw-es-replication-error-other-exception', $error->get( 'exception_error' ) ], Message::PARSE, $this->languageCode );
			$error_code = 'smw-es-replication-error-other-exception';
		}

		$this->templateEngine->compile(
			'text_template',
			[
				'error_code' => $error_code,
				'text' => $text
			]
		);

		$this->templateEngine->compile(
			'comment_template',
			[
				'comment' => $this->msg( 'smw-es-replication-error-suggestions-exception', Message::TEXT, $this->languageCode )
			]
		);

		$html .= $this->templateEngine->code( 'text_template' );
		$html .= $this->templateEngine->code( 'line_template' );
		$html .= $this->templateEngine->code( 'comment_template' );

		return $html;
	}

	private function modificationDateDiffError( ReplicationError $error, $title_text ) {

		$html = '';

		$this->templateEngine->compile(
			'text_template',
			[
				'error_code' => 'smw-es-replication-error-divergent-date',
				'text' => $this->msg( [ 'smw-es-replication-error-divergent-date', $title_text, $error->get( 'id' ) ], Message::TEXT, $this->languageCode )
			]
		);

		$this->templateEngine->compile(
			'comment_template',
			[
				'comment' => $this->msg( 'smw-es-replication-error-suggestions', Message::TEXT, $this->languageCode )
			]
		);

		$this->templateEngine->compile(
			'compare_template',
			[
				'explain' => $this->msg( 'smw-es-replication-error-divergent-date-short', Message::TEXT, $this->languageCode ),
				'es_key' => 'Elasticsearch',
				'es_value' => $error->get( 'time_es' ),
				'backend_key' => 'Database',
				'backend_value' => $error->get( 'time_store' )
			]
		);

		$html .= $this->templateEngine->code( 'text_template' );
		$html .= $this->templateEngine->code( 'line_template' );
		$html .= $this->templateEngine->code( 'compare_template' );
		$html .= $this->templateEngine->code( 'line_template' );
		$html .= $this->templateEngine->code( 'comment_template' );

		return $html;
	}

	private function associatedRevisionDiffError( ReplicationError $error, $title_text ) {

		$html = '';

		$this->severityType = self::SEVERITY_TYPE_WARNING;
		$this->errorTitle = 'smw-es-replication-maintenance-mode';

		$this->templateEngine->compile(
			'text_template',
			[
				'error_code' => 'smw-es-replication-error-divergent-revision',
				'text' => $this->msg( [ 'smw-es-replication-error-divergent-revision', $title_text, $error->get( 'id' ) ], Message::TEXT, $this->languageCode )
			]
		);

		$this->templateEngine->compile(
			'comment_template',
			[
				'comment' => $this->msg( 'smw-es-replication-error-suggestions', Message::TEXT, $this->languageCode )
			]
		);

		$this->templateEngine->compile(
			'compare_template',
			[
				'explain' => $this->msg( 'smw-es-replication-error-divergent-revision-short', Message::TEXT, $this->languageCode ),
				'es_key' => 'Elasticsearch',
				'es_value' => $error->get( 'rev_es' ),
				'backend_key' => 'Database',
				'backend_value' => $error->get( 'rev_store' )
			]
		);

		$html .= $this->templateEngine->code( 'text_template' );
		$html .= $this->templateEngine->code( 'line_template' );
		$html .= $this->templateEngine->code( 'compare_template' );
		$html .= $this->templateEngine->code( 'line_template' );
		$html .= $this->templateEngine->code( 'comment_template' );

		return $html;
	}

	private function missingDocumentError( ReplicationError $error, $title_text ) {

		$html = '';

		$this->severityType = self::SEVERITY_TYPE_ERROR;
		$this->templateEngine->compile(
			'text_template',
			[
				'error_code' => 'smw-es-replication-error-missing-id',
				'text' => $this->msg( [ 'smw-es-replication-error-missing-id', $title_text, $error->get( 'id' ) ], Message::TEXT, $this->languageCode )
			]
		);

		$this->templateEngine->compile(
			'comment_template',
			[
				'comment' => $this->msg( 'smw-es-replication-error-suggestions', Message::TEXT, $this->languageCode )
			]
		);

		$html .= $this->templateEngine->code( 'text_template' );
		$html .= $this->templateEngine->code( 'line_template' );
		$html .= $this->templateEngine->code( 'comment_template' );

		return $html;
	}

	private function fileAttachmentError( ReplicationError $error, $title_text ) {

		$html = '';
		$this->severityType = self::SEVERITY_TYPE_WARNING;

		$this->templateEngine->compile(
			'text_template',
			[
				'error_code' => 'smw-es-replication-error-file-ingest-missing-file-attachment',
				'text' => $this->msg( [ 'smw-es-replication-error-file-ingest-missing-file-attachment', $title_text ], Message::PARSE, $this->languageCode )
			]
		);

		$this->templateEngine->compile(
			'comment_template',
			[
				'comment' => $this->msg( 'smw-es-replication-error-file-ingest-missing-file-attachment-suggestions', Message::PARSE, $this->languageCode )
			]
		);

		$html .= $this->templateEngine->code( 'text_template' );
		$html .= $this->templateEngine->code( 'line_template' );
		$html .= $this->templateEngine->code( 'comment_template' );

		return $html;
	}

	private function wrapHTML( $content ) {

		if ( $content === '' ) {
			return '';
		}

		return $content;
	}

}
