<?php

namespace SMW\Elastic\Indexer\Replication;

use MediaWiki\Title\Title;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\EntityCache;
use SMW\Localizer\Message;
use SMW\Localizer\MessageLocalizerTrait;
use SMW\Store;
use SMW\Utils\TemplateEngine;

/**
 * @license GPL-2.0-or-later
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

	private ?TemplateEngine $templateEngine = null;

	private string $errorTitle = '';

	private mixed $languageCode = '';

	private string $severityType = self::SEVERITY_TYPE_ERROR;

	private int $cacheTTL = 3600;

	/**
	 * @since 3.1
	 */
	public function __construct(
		private Store $store,
		private DocumentReplicationExaminer $documentReplicationExaminer,
		private EntityCache $entityCache,
	) {
	}

	/**
	 * @since 3.1
	 *
	 * @param WikiPage $subject
	 */
	public static function makeCacheKey( $subject ): string {
		if ( $subject instanceof WikiPage ) {
			$subject = $subject->getHash();
		}

		return EntityCache::makeCacheKey( 'es-replication-check', $subject );
	}

	/**
	 * @since 3.1
	 */
	public function getReplicationFailures() {
		return $this->entityCache->fetch( $this->makeCacheKey( self::REPLICATION_CHECK_TASK_CACKE_KEY ) );
	}

	/**
	 * @since 3.1
	 */
	public function deleteEntireReplicationTrail(): void {
		$this->entityCache->delete( $this->makeCacheKey( self::REPLICATION_CHECK_TASK_CACKE_KEY ) );
	}

	/**
	 * @since 3.1
	 *
	 * @param WikiPage|Title $subject
	 */
	public function deleteReplicationTrail( $subject ): void {
		if ( $subject instanceof Title ) {
			$subject = WikiPage::newFromTitle( $subject );
		}

		if ( !$subject instanceof WikiPage ) {
			return;
		}

		$this->entityCache->deleteSub(
			$this->makeCacheKey( self::REPLICATION_CHECK_TASK_CACKE_KEY ),
			$this->makeCacheKey( $subject )
		);
	}

	/**
	 * @since 3.1
	 */
	public function setCacheTTL( mixed $cacheTTL ): void {
		$this->cacheTTL = (int)$cacheTTL > 0 ? (int)$cacheTTL : 3600;
	}

	/**
	 * @since 3.2
	 */
	public function getErrorTitle(): string {
		return $this->errorTitle;
	}

	/**
	 * @since 3.2
	 */
	public function getSeverityType(): string {
		return $this->severityType;
	}

	/**
	 * @since 3.1
	 */
	public function process( array $parameters ): array {
		if ( !isset( $parameters['subject'] ) || $parameters['subject'] === '' ) {
			return [ 'done' => false ];
		}

		$subject = WikiPage::doUnserialize(
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
	 */
	public function checkReplication( WikiPage $subject, array $options = [] ): string {
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

		return $this->check( $subject );
	}

	private function check( WikiPage $subject ): string {
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
			$property = Property::newFromUserLabel( $subject->getDBKey() );
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

	private function buildHTML( ReplicationError $error, $title_text ): string {
		$this->errorTitle = 'smw-es-replication-error';

		if ( $error->is( ReplicationError::TYPE_EXCEPTION ) ) {
			$html = $this->exceptionError( $error );
		} elseif ( $error->is( ReplicationError::TYPE_MODIFICATION_DATE_DIFF ) ) {
			$html = $this->modificationDateDiffError( $error, $title_text );
		} elseif ( $error->is( ReplicationError::TYPE_ASSOCIATED_REVISION_DIFF ) ) {
			$html = $this->associatedRevisionDiffError( $error, $title_text );
		} elseif ( $error->is( ReplicationError::TYPE_FILE_ATTACHMENT_MISSING ) ) {
			$html = $this->fileAttachmentError( $title_text );
		} else {
			$html = $this->missingDocumentError( $error, $title_text );
		}

		return $html;
	}

	private function connectionError(): string {
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

	private function maintenanceError(): string {
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

	private function exceptionError( ReplicationError $error ): string {
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

	private function modificationDateDiffError( ReplicationError $error, $title_text ): string {
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

	private function associatedRevisionDiffError( ReplicationError $error, $title_text ): string {
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

	private function missingDocumentError( ReplicationError $error, $title_text ): string {
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

	private function fileAttachmentError( $title_text ): string {
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

	private function wrapHTML( string $content ): string {
		if ( $content === '' ) {
			return '';
		}

		return $content;
	}

}
