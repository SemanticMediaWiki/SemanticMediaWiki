<?php

namespace SMW\Indicator\EntityExaminerIndicators;

use SMW\Message;
use SMW\Store;
use SMW\EntityCache;
use SMW\DIWikiPage;
use SMW\RequestOptions;
use SMW\Constraint\ConstraintError;
use SMW\Indicator\IndicatorProviders\TypableSeverityIndicatorProvider;
use SMW\Utils\TemplateEngine;
use SMW\Localizer\MessageLocalizerTrait;
use Html;

/**
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ConstraintErrorEntityExaminerIndicatorProvider implements TypableSeverityIndicatorProvider {

	use MessageLocalizerTrait;

	const LOOKUP_LIMIT = 20;

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var EntityCache
	 */
	private $entityCache;

	/**
	 * @var boolean
	 */
	private $checkConstraintErrors = true;

	/**
	 * @var []
	 */
	protected $indicators = [];

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
	 * @param EntityCache $entityCache
	 */
	public function __construct( Store $store, EntityCache $entityCache ) {
		$this->store = $store;
		$this->entityCache = $entityCache;
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
		return 'smw-entity-examiner-deferred-constraint-error';
	}

	/**
	 * @since 3.2
	 *
	 * @param boolean $checkConstraintErrors
	 */
	public function setConstraintErrorCheck( $checkConstraintErrors ) {
		$this->checkConstraintErrors = $checkConstraintErrors;
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

		if ( $this->checkConstraintErrors ) {
			$this->checkConstraintErrors( $subject, $options );
		}

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
		// The standard helplink interferes with the alignment (due to a text
		// component) therefore disabled it when indicators are present
		return '#mw-indicator-mw-helplink {display:none;}';
	}

	protected function checkConstraintErrors( $subject, $options ) {
		$this->runCheck( $subject, $options );
	}

	protected function runCheck( $subject, $options ) {

		$this->languageCode = $options['uselang'] ?? Message::USER_LANGUAGE;

		$errors = $this->findErrors( $subject );
		$top = '';

		if ( $errors === [] ) {
			return $this->indicators = [
				'id'      => $this->getName(),
				'content' => '',
			];
		}

		$this->errorTitle = 'smw-constraint-error';
		$this->severityType = TypableSeverityIndicatorProvider::SEVERITY_WARNING;

		$this->templateEngine = new TemplateEngine();

		$this->templateEngine->bulkLoad(
			[
				'/indicator/composite.line.ms' => 'line_template',
				'/indicator/bottom.marker.ms' => 'bottom_marker',
				'/indicator/bottom.sticky.ms' => 'bottom_sticky_template',
				'/indicator/comment.ms' => 'comment_template',
				'/constraint/constraint.error.top.line.ms' => 'top_line_template',
				'/constraint/constraint.sticky.top.ms' => 'sticky_top_template'
			]
		);

		$this->templateEngine->compile(
			'top_line_template',
			[
				'margin' => isset( $options['dir'] ) && $options['dir'] === 'rtl' ? 'right' : 'left'
			]
		);

		$this->templateEngine->compile(
			'line_template',
			[
				'margin' => isset( $options['dir'] ) && $options['dir'] === 'rtl' ? 'right' : 'left'
			]
		);

		$this->templateEngine->compile(
			'comment_template',
			[
				'comment' => $this->msg( 'smw-constraint-error-suggestions', Message::TEXT, $this->languageCode )
			]
		);

		$this->templateEngine->compile(
			'bottom_marker',
			[
				'margin' => isset( $options['dir'] ) && $options['dir'] === 'rtl' ? 'right' : 'left',
				'label' => 'constraint',
				'background-color' => '#00BCD4',
				'color' => '#ffffff'
			]
		);

		if ( count( $errors ) >= self::LOOKUP_LIMIT ) {
			$top = $this->msg( [ 'smw-constraint-error-limit', self::LOOKUP_LIMIT ], Message::TEXT, $this->languageCode );
			$top .= $this->templateEngine->code( 'top_line_template' );

			$this->templateEngine->compile( 'sticky_top_template', [ 'content' => $top ] );
			$top = $this->templateEngine->code( 'sticky_top_template' );
			$content = '<div><ul><li>' . implode( '</li><li>', $errors ) . '</li></ul></div>';
		} else {
			$content = '<div style="padding-top:10px;"><ul><li>' . implode( '</li><li>', $errors ) . '</li></ul></div>';
		}

		$bottom = $this->templateEngine->code( 'line_template' );
		$bottom .= $this->templateEngine->code( 'comment_template' );

		if ( count( $errors ) >= 3 ) {
			$this->templateEngine->compile(
				'bottom_sticky_template',
				[
					'content' => $this->templateEngine->code( 'bottom_marker' )
				]
			);

			$bottom .= $this->templateEngine->code( 'bottom_sticky_template' );
		} else {
			$bottom .= $this->templateEngine->code( 'bottom_marker' );
		}

		$title = $this->msg(
			[ 'smw-indicator-constraint-violation', count( $errors ) ],
			Message::TEXT,
			$this->languageCode
		);

		$this->indicators = [
			'id'      => $this->getName(),
			'title'   => $title,
			'content' => $top . $content . $bottom,
		];
	}

	private function findErrors( $subject ) {

		$key = $this->entityCache->makeKey( $subject, 'constraint-error' );

		if ( ( $errors = $this->entityCache->fetch( $key ) ) !== false ) {
			return $this->decodeErrors( $errors );
		}

		$errorLookup = $this->store->service( 'ErrorLookup' );

		$requestOptions = new RequestOptions();
		$requestOptions->setCaller( __METHOD__ );
		$requestOptions->setOption( 'checkConstraintErrors', $this->checkConstraintErrors );
		$requestOptions->setLimit( self::LOOKUP_LIMIT );

		$res = $errorLookup->findErrorsByType(
			ConstraintError::ERROR_TYPE,
			$subject,
			$requestOptions
		);

		$errors = $errorLookup->buildArray( $res );

		// Store `null` as string to have the cache return something and not
		// interpret an empty [] as `false`
		if ( $errors === [] ) {
			$errors = 'null';
		}

		$this->entityCache->save( $key, $errors, EntityCache::TTL_WEEK );

		// Being an associate member means once the subject is invalidated (during
		// save, delete etc.), the current cache entry is evicted as well.
		$this->entityCache->associate( $subject, $key );

		return $this->decodeErrors( $errors );
	}

	private function decodeErrors( $errors ) {

		if ( $errors === 'null' ) {
			return [];
		}

		$messages = [];

		foreach ( $errors as $error ) {
			$messages[] = Message::decode( $error, Message::PARSE, $this->languageCode );
		}

		return $messages;
	}

}
