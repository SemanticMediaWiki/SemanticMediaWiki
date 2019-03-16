<?php

namespace SMW\Property;

/**
 * Interface specifying available methods to interact with the Decorator
 *
 * @license GNU GPL v2+
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
