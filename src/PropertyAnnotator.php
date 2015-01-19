<?php

namespace SMW;

/**
 * Interface specifing available methods to interact with the Decorator
 *
 * @ingroup SMW
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
interface PropertyAnnotator {

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
