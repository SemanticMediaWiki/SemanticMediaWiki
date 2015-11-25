<?php

namespace SMW;

/**
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class SearchField {

	/**
	 * @since 2.4
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function getSearchStringFrom( $string ) {
		return self::getIndexStringFrom( $string );
	}

	/**
	 * @since 2.4
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function getIndexStringFrom( $string ) {
		$string = str_replace(
			array( "http://", "https://", "mailto:", "tel:" ),
			array( '' ),
			$string
		);

		return mb_strtolower( $string );
	}

}
