<?php

namespace SMW\Property;

use SMW\DataItems\Property;
use SMW\DataModel\SemanticData;

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
	 * @return ?SemanticData
	 */
	public function getSemanticData();

	/**
	 * @since 3.1
	 *
	 * @return array[]
	 */
	public function getMessages(): array;

	/**
	 * @since 3.1
	 *
	 * @return bool
	 */
	public function isLocked();

	/**
	 * @since 3.1
	 *
	 * @param Property $property
	 */
	public function check( Property $property );

}
