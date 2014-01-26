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
	 * @see PropertyAnnotatorDecorator::addPropertyValues
	 */
	protected function addPropertyValues() {

		$title = $this->createRedirectTargetFromText( $this->text );

		if ( $title instanceOf Title ) {
			$this->getSemanticData()->addPropertyObjectValue(
				new DIProperty( '_REDI' ),
				DIWikiPage::newFromTitle( $title, '__red' )
			);
		}

	}

	protected function createRedirectTargetFromText( $text ) {

		if ( $this->hasContentHandler() ) {
			return ContentHandler::makeContent( $text, null, CONTENT_MODEL_WIKITEXT )->getRedirectTarget();
		}

		return Title::newFromRedirect( $text );
	}

	protected function hasContentHandler() {
		return class_exists( 'ContentHandler' );
	}

}
