<?php

namespace SMW;

use ContentHandler;
use Title;

/**
 * Handling redirect annotation
 *
 * @ingroup SMW
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class RedirectPropertyAnnotator extends PropertyAnnotatorDecorator {

	/** @var string */
	protected $text;

	/**
	 * @since 1.9
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param string $text
	 */
	public function __construct( PropertyAnnotator $propertyAnnotator, $text ) {
		parent::__construct( $propertyAnnotator );
		$this->text = $text;
	}

	/**
	 * @see PropertyAnnotator::addAnnotation
	 *
	 * @since 1.9
	 */
	public function addAnnotation() {

		$title = $this->newTitleFromText( $this->text );

		if ( $title !== null ) {
			$this->getSemanticData()->addPropertyObjectValue(
				new DIProperty( '_REDI' ),
				DIWikiPage::newFromTitle( $title, '__red' )
			);
		}

		return $this;
	}

	/**
	 * @note ContentHandler got introduced with MW 1.21
	 *
	 * @since 1.9
	 *
	 * @param  string $text
	 *
	 * @return Title|null
	 */
	protected function newTitleFromText( $text ) {

		$title = null;

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
