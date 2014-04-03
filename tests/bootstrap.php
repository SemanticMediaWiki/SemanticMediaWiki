<?php

if ( php_sapi_name() !== 'cli' ) {
	die( 'Not an entry point' );
}

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'MediaWiki is not available for the test environment' );
}

include_once 'ClassMapGenerator.php';

function registerClassLoader( $path, $message ) {
	print( $message );
	return require $path;
}

function registerClassMap( $loader, $path ) {

	if ( !is_readable( $path ) ) {
		die( 'Path is not accessible' );
	}

	$loader->addClassMap( ClassMapGenerator::createMap( $path ) );
}

function useTestLoader() {

	$mwVendorPath = __DIR__ . '/../../../vendor/autoload.php';
	$localVendorPath = __DIR__ . '/../vendor/autoload.php';

	if ( is_readable( $localVendorPath ) ) {
		$loader = registerClassLoader( $localVendorPath, "\Using the local vendor class loader ...\n" );
	} elseif ( is_readable( $mwVendorPath ) ) {
		$loader = registerClassLoader( $mwVendorPath, "\nUsing the MediaWiki vendor class loader ...\n" );
	}

	if ( !$loader instanceof \Composer\Autoload\ClassLoader ) {
		return false;
	}

	registerClassMap( $loader, __DIR__ . '/phpunit' );
	registerClassMap( $loader, __DIR__ . '/../maintenance' );

	return true;
}

if ( !useTestLoader() ) {
	die( 'Required test class loader was not accessible' );
}
