<?php

namespace SMW\Constraint;

use SMW\Message;
use SMW\Store;
use SMW\DIWikiPage;
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

	/**
	 * @var Store
	 */
	private $store;

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
	 */
	public function __construct( Store $store ) {
		$this->store = $store;
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

		$errorLookup = $this->store->service( 'ErrorLookup' );
		$connection = $this->store->getConnection( 'mw.db' );

		$res = $errorLookup->findErrorsByType( 'constraint',  DIWikiPage::newfromTitle( $title ) );

		$messages = [];

		foreach ( $res as $row ) {

			if ( $row->o_blob !== null ) {
				$msg = $connection->unescape_bytea( $row->o_blob );
			} else {
				$msg = $row->o_hash;
			}

			$messages[] = Message::decode( $msg );
		}

		if ( $messages === [] ) {
			return;
		}

		$this->errorTitle = 'smw-constraint-error';

		$this->templateEngine = new TemplateEngine();
		$this->templateEngine->load( '/constraint/ConstraintErrorLine.ms', 'line_template' );

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
				'comment' => Message::get( 'smw-constraint-error-suggestions' )
			]
		);

		$content = '<ul><li>' . implode('</li><li>', $messages ) . '</li></ul>';

		$bottom = $this->templateEngine->code( 'comment_template' );
		$bottom .= $this->templateEngine->code( 'line_template' );

		$this->templateEngine->load( '/constraint/ConstraintErrorHighlighter.ms', 'highlighter_template' );

		$this->templateEngine->compile(
			'highlighter_template',
			[
				'title' => Message::get( $this->errorTitle ),
				'content' => htmlspecialchars( $content, ENT_QUOTES ),
				'bottom'  => htmlspecialchars( $bottom, ENT_QUOTES ),
			]
		);

		$html = $this->templateEngine->code( 'highlighter_template' );

		$this->indicators['smw-w-constraint'] = $html;
	}

}
