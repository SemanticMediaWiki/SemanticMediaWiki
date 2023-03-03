<?php

namespace SMW;

class Globals {

	/**
	 * PHP8.1 Compatibility
	 * @internal
	 */
	public static function replace( array $vars ): void {
		foreach ( $vars as $key => $value ) {
			$GLOBALS[$key] = $value;
		}
	}
}
