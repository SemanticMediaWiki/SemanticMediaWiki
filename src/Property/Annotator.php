<?php

namespace SMW\Property;

use SMW\PropertyAnnotator;
use SMW\SemanticData;

/**
 * Interface specifying available methods to interact with the Decorator
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
interface Annotator {

	/**
	 * Returns a SemanticData container
	 *
	 * @since 1.9
	 *
	 * @return SemanticData
	 */
	public function getSemanticData();

	/**
	 * Add annotations to the SemanticData container
	 *
	 * @since 1.9
	 *
	 * @return PropertyAnnotator
	 */
	public function addAnnotation();

}
