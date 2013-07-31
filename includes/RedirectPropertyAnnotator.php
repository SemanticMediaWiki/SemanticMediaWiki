<?php

namespace SMW;

use ContentHandler;
use Title;

/**
 * Adds a '_REDI' property annotation where a redirect is being indicated
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Adds a '_REDI' property annotation where a redirect is being indicated
 *
 * @ingroup Annotator
 */
class RedirectPropertyAnnotator {

	/** @var SemanticData */
	protected $semanticData = null;

	/** @var boolean */
	protected $isEnabled = true;

	/**
	 * @since 1.9
	 *
	 * @param SemanticData $semanticData
	 */
	public function __construct( SemanticData $semanticData ) {
		$this->semanticData = $semanticData;
	}

	/**
	 * Indicates if the current object instance can
	 * build a redirect
	 *
	 * @since 1.9
	 *
	 * @param boolean $canAnnotate
	 *
	 * @return RedirectPropertyAnnotator
	 */
	public function isEnabled( $enabled = true ) {
		$this->isEnabled = $enabled;
		return $this;
	}

	/**
	 * Builds a '_REDI' property and attaches it to the
	 * available semantic data container
	 *
	 * @par Example:
	 * @code
	 *  $redirect = new RedirectPropertyAnnotator( $semanticData );
	 *  $redirect->isEnabled( true )->annotate( $text );
	 * @endcode
	 *
	 * @since 1.9
	 *
	 * @param vargs
	 *
	 * @return string
	 */
	public function annotate( /* ... */ ) {

		$argument = func_get_arg( 0 );

		if ( $this->isEnabled && is_string( $argument ) ) {
			$title = $this->buildFromText( $argument );
		} else if ( $this->isEnabled && $argument instanceof Title ) {
			$title = $argument;
		} else {
			$title = null;
		}

		if ( $title !== null ) {
			$this->semanticData->addPropertyObjectValue( new DIProperty( '_REDI' ), DIWikiPage::newFromTitle( $title, '__red' ) );
		}

	}

	/**
	 * Extract a redirect destination from a string and return the Title,
	 * or null if the text doesn't contain a valid redirect
	 *
	 * @note ContentHandler got introduced with Mw 1.21
	 *
	 * @since 1.9
	 *
	 * @param  string $text
	 *
	 * @return Title|null
	 */
	protected function buildFromText( $text ) {

		if ( class_exists( 'ContentHandler' ) ) {
			$title = ContentHandler::makeContent( $text, null, CONTENT_MODEL_WIKITEXT )->getRedirectTarget();
		} else {
			// @codeCoverageIgnoreStart
			$title = Title::newFromRedirect( $text );
			// @codeCoverageIgnoreEnd
		}

		return $title;
	}
}
