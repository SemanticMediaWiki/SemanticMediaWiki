<?php

namespace SMW\Constraint;

use SMW\Message;
use SMW\Store;
use SMW\EntityCache;
use SMW\DIWikiPage;
use SMW\RequestOptions;
use SMW\MediaWiki\IndicatorProvider;
use SMW\Utils\TemplateEngine;
use Title;
use Html;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class ConstraintErrorIndicatorProvider implements IndicatorProvider {

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
	private $indicators = [];

	/**
	 * @since 3.1
	 *
	 * @param Store $store
	 * @param EntityCache $entityCache
	 */
	public function __construct( Store $store, EntityCache $entityCache ) {
		$this->store = $store;
		$this->entityCache = $entityCache;
	}

	/**
	 * @since 3.1
	 *
	 * @param boolean $checkConstraintErrors
	 */
	public function setConstraintErrorCheck( $checkConstraintErrors ) {
		$this->checkConstraintErrors = $checkConstraintErrors;
	}

	/**
	 * @since 3.1
	 *
	 * @param Title $title
	 * @param array $options
	 *
	 * @return boolean
	 */
	public function hasIndicator( Title $title, $options ) {

		if ( $this->checkConstraintErrors && $title->exists() ) {
			$this->checkConstraintErrors( $title, $options );
		}

		return $this->indicators !== [];
	}

	/**
	 * @since 3.1
	 *
	 * @return []
	 */
	public function getIndicators() {
		return $this->indicators;
	}

	/**
	 * @since 3.1
	 *
	 * @return []
	 */
	public function getModules() {
		return [];
	}

	/**
	 * @since 3.1
	 *
	 * @return string
	 */
	public function getInlineStyle() {
		// The standard helplink interferes with the alignment (due to a text
		// component) therefore disabled it when indicators are present
		return '#mw-indicator-mw-helplink {display:none;}';
	}

	private function checkConstraintErrors( $title, $options ) {

		if ( $options['action'] === 'edit' || $options['diff'] !== null || $options['action'] === 'history' ) {
			return;
		}

		$errors = $this->findErrors(
			DIWikiPage::newfromTitle( $title )
		);

		if ( $errors === [] ) {
			return;
		}

		$this->errorTitle = 'smw-constraint-error';

		$this->templateEngine = new TemplateEngine();
		$this->templateEngine->load( '/constraint/ConstraintErrorLine.ms', 'line_template' );
		$this->templateEngine->load( '/constraint/ConstraintErrorTopLine.ms', 'top_line_template' );

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

		$this->templateEngine->load( '/constraint/ConstraintErrorComment.ms', 'comment_template' );

		$this->templateEngine->compile(
			'comment_template',
			[
				'comment' => Message::get( 'smw-constraint-error-suggestions', Message::TEXT, Message::USER_LANGUAGE )
			]
		);

		if ( count( $errors ) >= self::LOOKUP_LIMIT ) {
			$top = Message::get( [ 'smw-constraint-error-limit', self::LOOKUP_LIMIT ], Message::TEXT, Message::USER_LANGUAGE );
			$top .= $this->templateEngine->code( 'top_line_template' );
		} else {
			$top = '';
		}

		$content = '<ul><li>' . implode('</li><li>', $errors ) . '</li></ul>';

		$bottom = $this->templateEngine->code( 'comment_template' );
		$bottom .= $this->templateEngine->code( 'line_template' );

		$this->templateEngine->load( '/constraint/ConstraintErrorHighlighter.ms', 'highlighter_template' );

		$this->templateEngine->compile(
			'highlighter_template',
			[
				'title' => Message::get( $this->errorTitle, Message::TEXT, Message::USER_LANGUAGE ),
				'content' => htmlspecialchars( $content, ENT_QUOTES ),
				'top'  => htmlspecialchars( $top, ENT_QUOTES ),
				'bottom'  => htmlspecialchars( $bottom, ENT_QUOTES ),
			]
		);

		$html = $this->templateEngine->code( 'highlighter_template' );

		$this->indicators['smw-w-constraint'] = $html;
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
			$messages[] = Message::decode( $error, null, Message::USER_LANGUAGE );
		}

		return $messages;
	}

}
