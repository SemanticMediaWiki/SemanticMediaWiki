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
	 * @var RedirectTargetFinder
	 */
	private $redirectTargetFinder;

	/**
	 * @since 1.9
	 *
	 * @param Annotator $propertyAnnotator
	 * @param RedirectTargetFinder $redirectTargetFinder
	 */
	public function __construct( Annotator $propertyAnnotator, RedirectTargetFinder $redirectTargetFinder ) {
		parent::__construct( $propertyAnnotator );
		$this->redirectTargetFinder = $redirectTargetFinder;
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
