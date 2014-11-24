<?php

namespace SMW;

use Title;

/**
 * Utility class to modify style attributes
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class UiAttributeFinder {

	/**
	 * @since 2.1
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public static function getClassForId( $id ) {

		$id = strtolower( $id );

		if ( isset( $GLOBALS['smwgUiClassAttributes'][ $id ] ) ) {
			return htmlspecialchars( $GLOBALS['smwgUiClassAttributes'][ $id ] );
		}

		return '';
	}

}
