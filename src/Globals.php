<?php

namespace SMW;

class Globals {

	/**
	 * PHP8.1 Compatibility
	 * @since 4.0
	 * @param array $vars
	 * @return void
	 */
	public static function replace( array $vars ): void {
		foreach ( $vars as $key => $value ) {
			$GLOBALS[$key] = $value;
		}
	}
}
