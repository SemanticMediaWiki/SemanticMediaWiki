<?php

/*
 * @see Symfony/Component/ClassLoader/ClassMapGenerator.php
 *
 * Copied from the Symfony package
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * @license MIT
 */
class ClassMapGenerator {

	/**
	 * @return array
	 */
	public static function createMap( $dir ) {

		if ( is_string( $dir ) ) {
			$dir = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $dir ) );
		}

		$map = array();

		foreach ( $dir as $file ) {
			if ( !$file->isFile() ) {
				continue;
			}

			$path = $file->getRealPath();

			if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') {
				continue;
			}

			$classes = self::findClasses($path);

			foreach ($classes as $class) {
				$map[$class] = $path;
			}

		}

		return $map;
	}

	private static function findClasses( $path ) {

		$contents = file_get_contents($path);
		$tokens   = token_get_all($contents);
		$T_TRAIT  = version_compare(PHP_VERSION, '5.4', '<') ? -1 : T_TRAIT;

		$classes = array();

		$namespace = '';
		for ($i = 0, $max = count($tokens); $i < $max; $i++) {
			$token = $tokens[$i];

			if (is_string($token)) {
				continue;
			}

			$class = '';

			switch ($token[0]) {
				case T_NAMESPACE:
					$namespace = '';
					// If there is a namespace, extract it
					while (($t = $tokens[++$i]) && is_array($t)) {
						if (in_array($t[0], array(T_STRING, T_NS_SEPARATOR))) {
							$namespace .= $t[1];
						}
					}
					$namespace .= '\\';
					break;
				case T_CLASS:
				case T_INTERFACE:
				case $T_TRAIT:
					// Find the classname
					while (($t = $tokens[++$i]) && is_array($t)) {
						if (T_STRING === $t[0]) {
							$class .= $t[1];
						} elseif ($class !== '' && T_WHITESPACE == $t[0]) {
							break;
						}
					}

					$classes[] = ltrim($namespace.$class, '\\');
					break;
				default:
					break;
			}
		}

		return $classes;
	}

}
