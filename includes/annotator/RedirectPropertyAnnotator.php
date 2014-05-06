<?php

namespace SMW;

use Title;

/**
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class RedirectPropertyAnnotator extends PropertyAnnotatorDecorator {

	protected $redirectTarget;

	/**
	 * @since 1.9
	 *
	 * @param PropertyAnnotator $propertyAnnotator
	 * @param Title|null $redirectTarget
	 */
	public function __construct( PropertyAnnotator $propertyAnnotator, Title $redirectTarget = null ) {
		parent::__construct( $propertyAnnotator );
		$this->redirectTarget = $redirectTarget;
	}

	/**
	 * @see PropertyAnnotatorDecorator::addPropertyValues
	 */
	protected function addPropertyValues() {

		if ( $this->redirectTarget === null ) {
			return null;
		}

		$this->getSemanticData()->addPropertyObjectValue(
			new DIProperty( '_REDI' ),
			DIWikiPage::newFromTitle( $this->redirectTarget, '__red' )
		);
	}

}
