<?php

/**
 * Lazy script to invoke the MediaWiki phpunit runner
 *
 * php mw-phpunit-runner.php [options]
 */

if ( php_sapi_name() !== 'cli' ) {
	die( 'Not an entry point' );
}

print( "\nMediaWiki phpunit runnner ... \n" );

function isAccessiblePath( $path ) {

	if ( is_readable( $path ) ) {
		return $path;
	}

	die( "Expected an accessible {$path}" );
}

$mw = isAccessiblePath( __DIR__ . "/../../../tests/phpunit/phpunit.php" );
$config = isAccessiblePath( __DIR__ . "/../phpunit.xml.dist" );

array_shift( $GLOBALS['argv'] );

return passthru( "php {$mw} -c {$config} ". implode( ' ', $GLOBALS['argv'] ) );
