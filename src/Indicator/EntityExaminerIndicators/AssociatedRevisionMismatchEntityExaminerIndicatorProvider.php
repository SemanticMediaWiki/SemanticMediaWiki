<?php

namespace SMW\Indicator\EntityExaminerIndicators;

use SMW\Message;
use SMW\Store;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\Indicator\IndicatorProviders\TypableSeverityIndicatorProvider;
use SMW\Indicator\IndicatorProviders\DeferrableIndicatorProvider;
use SMW\MediaWiki\RevisionGuardAwareTrait;
use SMW\Utils\TemplateEngine;
use SMW\Localizer\MessageLocalizerTrait;
use SMW\MediaWiki\Permission\PermissionAware;
use SMW\MediaWiki\Permission\PermissionExaminer;
use SMW\GroupPermissions;
use Title;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class AssociatedRevisionMismatchEntityExaminerIndicatorProvider implements TypableSeverityIndicatorProvider, DeferrableIndicatorProvider, PermissionAware {

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
	 * @var boolean
	 */
	private $isDeferredMode = false;

	/**
	 * @since 3.2
	 *
	 * @param Store $store
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
	}

	/**
	 * @see PermissionAware::hasPermission
	 * @since 3.2
	 *
	 * @param PermissionExaminer $permissionExaminer
	 *
	 * @return bool
	 */
	public function hasPermission( PermissionExaminer $permissionExaminer ) : bool {
		return $permissionExaminer->hasPermissionOf( GroupPermissions::VIEW_ENTITY_ASSOCIATEDREVISIONMISMATCH );
	}

	/**
	 * @since 3.2
	 *
	 * @param boolean $type
	 */
	public function setDeferredMode( bool $isDeferredMode ) {
		$this->isDeferredMode = $isDeferredMode;
	}

	/**
	 * @since 3.2
	 *
	 * @return boolean
	 */
	public function isDeferredMode() : bool {
		return $this->isDeferredMode;
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

		if ( $this->isDeferredMode ) {
			return $this->runCheck( $subject, $options );
		}

		$this->indicators = [ 'id' => $this->getName() ];

		return $this->indicators !== [];
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

	private function runCheck( $subject, $options ) {

		$this->indicators = [];

		$latestRevID = $this->revisionGuard->getLatestRevID(
			$subject->getTitle()
		);

		// Make sure to match the correct internal predefined property key
		// when it is not a user-defined property
		if ( $subject->getNamespace() === SMW_NS_PROPERTY ) {
			$property = DIProperty::newFromUserLabel( $subject->getDBKey() );

			if ( !$property->isUserDefined() ) {
				$subject = new DIWikiPage( $property->getKey(), SMW_NS_PROPERTY );
			}
		}

		$associatedRev = (int)$this->store->getObjectIds()->findAssociatedRev(
			$subject->getDBKey(),
			$subject->getNamespace()
		);

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
