<?php

namespace SMW\Utils;

/**
 * Utility class to resolve array access using the dot path syntax.
 *
 * For example, an arra with [ 'foo' => [ 'bar' => [ 'foobar' => 42 ] ] ] can be
 * accessed using the simaple `foo.bar.foobar` path.
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class DotArray {

	/**
	 * @since 3.2
	 *
	 * @param array $array
	 * @param string $key
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public static function get( array $array, $key, $default = false ) {

		if ( strpos( $key, '.' ) !== false ) {
			return self::find( $array, explode( '.', $key ), $default );
		}

		if ( isset( $array[$key] ) ) {
			return $array[$key];
		}

		return $default;
	}

	private static function find( $array, $paths, $default ) {
		$key = '';

		foreach ( $paths as $k => $p ) {

			// Rebuilding the path to check whether the path is accessible
			// or not, repeat that recursively until all path elements are
			// resolved
			$key .= $key === '' ? "$p" : ".$p";
			unset( $paths[$k] );

			// Something like `foo.bar.x` where `foo.bar` has no array entry and
			// is a blank element
			if ( !isset( $array[$key] ) ) {
				continue;
			}

			// Has only one path element left (path is sequential the check is
			// sufficient and avoids counting)
			if ( isset( $paths[0] ) && !isset( $paths[1] ) ) {
				continue;
			}

			// No path left, use the last key as access node
			if ( $paths === [] ) {
				return $array[$key];
			}

			return self::find( $array[$key], $paths, $default );
		}

		return $default;
	}

}
