<?php

namespace SMW\Indicator\EntityExaminerIndicators;

use SMW\Message;
use SMW\Store;
use SMW\DIWikiPage;
use SMW\Indicator\IndicatorProviders\TypableSeverityIndicatorProvider;
use SMW\MediaWiki\RevisionGuardAwareTrait;
use SMW\Utils\TemplateEngine;
use SMW\Localizer\MessageLocalizerTrait;
use Title;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class AssociatedRevisionMismatchEntityExaminerIndicatorProvider implements TypableSeverityIndicatorProvider {

	use MessageLocalizerTrait;
	use RevisionGuardAwareTrait;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var []
	 */
	private $indicators = [];

	/**
	 * @var string
	 */
	private $severityType = '';

	/**
	 * @var string
	 */
	private $languageCode = '';

	/**
	 * @since 3.2
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @since 3.2
	 *
	 * @param string $severityType
	 *
	 * @return boolean
	 */
	public function isSeverityType( string $severityType ) : bool {
		return $this->severityType === $severityType;
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getName() : string {
		return 'smw-entity-examiner-associated-revision-mismatch';
	}

	/**
	 * @since 3.2
	 *
	 * @param DIWikiPage $subject
	 * @param array $options
	 *
	 * @return boolean
	 */
	public function hasIndicator( DIWikiPage $subject, array $options ) {
		return $this->checkForMismatch( $subject, $options );
	}

	/**
	 * @since 3.2
	 *
	 * @return []
	 */
	public function getIndicators() {
		return $this->indicators;
	}

	/**
	 * @since 3.2
	 *
	 * @return []
	 */
	public function getModules() {
		return [];
	}

	/**
	 * @since 3.2
	 *
	 * @return string
	 */
	public function getInlineStyle() {
		return '';
	}

	private function checkForMismatch( $subject, $options ) {

		$this->indicators = [];

		$associatedRev = (int)$this->store->getObjectIds()->findAssociatedRev(
			$subject->getDBKey(),
			$subject->getNamespace()
		);

		$latestRevID = $this->revisionGuard->getLatestRevID( $subject->getTitle() );

		if ( $latestRevID != $associatedRev ) {
			$this->buildHTML( $latestRevID, $associatedRev, $options );
		}

		return $this->indicators !== [];
	}

	private function buildHTML( $latestRevID, $associatedRev, $options ) {

		$content = '';
		$this->severityType = TypableSeverityIndicatorProvider::SEVERITY_ERROR;

		$this->templateEngine = new TemplateEngine();
		$this->templateEngine->bulkLoad(
			[
				'/indicator/composite.line.ms' => 'line_template',
				'/indicator/comment.ms' => 'comment_template',
				'/indicator/bottom.comment.ms' => 'bottom_comment_template',
				'/indicator/text.ms' => 'text_template',
				'/indicator/compare.list.ms' => 'compare_list_template',
				'/indicator/bottom.sticky.ms' => 'bottom_sticky_template'
			]
		);

		$this->languageCode = $options['uselang'] ?? Message::USER_LANGUAGE;

		$this->templateEngine->compile(
			'line_template',
			[
				'margin' => isset( $options['dir'] ) && $options['dir'] === 'rtl' ? 'right' : 'left'
			]
		);

		$this->templateEngine->compile(
			'text_template',
			[
				'text' => $this->msg( [ 'smw-indicator-revision-mismatch-error' ], Message::PARSE, $this->languageCode )
			]
		);

		$this->templateEngine->compile(
			'compare_list_template',
			[
				'explain' => '',
				'first_key' => 'MediaWiki:',
				'first_value' => $latestRevID,
				'second_key' => 'Semantic MediaWiki:',
				'second_value' => $associatedRev
			]
		);

		$this->templateEngine->compile(
			'bottom_comment_template',
			[
				'comment' => $this->msg( 'smw-indicator-revision-mismatch-comment', Message::TEXT, $this->languageCode )
			]
		);

		$content .= $this->templateEngine->code( 'text_template' );
	//	$content .= $this->templateEngine->code( 'line_template' );
		$content .= $this->templateEngine->code( 'compare_list_template' );
		$content .= $this->templateEngine->code( 'line_template' );
		$content .= $this->templateEngine->code( 'bottom_comment_template' );

		$this->templateEngine->compile(
			'bottom_sticky_template',
			[
				'content' => $content
			]
		);

		$this->indicators = [
			'id' => $this->getName(),
			'title' => $this->msg( 'smw-indicator-revision-mismatch', Message::TEXT, $this->languageCode ),
			'content' => $content
		];
	}
}
