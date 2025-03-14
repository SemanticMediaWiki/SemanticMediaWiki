<?php

namespace SMW\Property;

use SMW\DIProperty;
use SMW\SemanticData;

/**
 * @license GPL-2.0-or-later
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
	 * @return array[]
	 */
	public function getMessages();

	/**
	 * @since 3.1
	 *
	 * @return bool
	 */
	public function isLocked();

	/**
	 * @since 3.1
	 *
	 * @param DIProperty $property
	 */
	public function check( DIProperty $property );

}
