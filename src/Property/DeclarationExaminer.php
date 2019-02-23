<?php

namespace SMW\Property;

use SMW\DIProperty;

/**
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
interface DeclarationExaminer {

	/**
	 * @since 3.1
	 *
	 * @return SemanticData
	 */
	public function getSemanticData();

	/**
	 * @since 3.1
	 *
	 * @return []
	 */
	public function getMessages();

	/**
	 * @since 3.1
	 *
	 * @return boolean
	 */
	public function isLocked();

	/**
	 * @since 3.1
	 *
	 * @param DIProperty $property
	 */
	public function check( DIProperty $property );

}
