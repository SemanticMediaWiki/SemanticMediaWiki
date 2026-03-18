<?php

namespace SMW\Property\Annotators;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\MediaWiki\RedirectTargetFinder;
use SMW\Property\Annotator;

/**
 * Handling redirect annotation
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class RedirectPropertyAnnotator extends PropertyAnnotatorDecorator {

	/**
	 * @since 1.9
	 */
	public function __construct(
		Annotator $propertyAnnotator,
		private readonly RedirectTargetFinder $redirectTargetFinder,
	) {
		parent::__construct( $propertyAnnotator );
	}

	/**
	 * @see PropertyAnnotatorDecorator::addPropertyValues
	 */
	protected function addPropertyValues() {
		if ( !$this->redirectTargetFinder->hasRedirectTarget() ) {
			return;
		}

		$this->getSemanticData()->addPropertyObjectValue(
			new DIProperty( '_REDI' ),
			DIWikiPage::newFromTitle( $this->redirectTargetFinder->getRedirectTarget() )
		);
	}

}
