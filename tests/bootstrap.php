<?php

if ( php_sapi_name() !== 'cli' ) {
	die( 'Not an entry point' );
}

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'MediaWiki is required for the test environment' );
}

function registerAutoloaderPath( $identifier, $path ) {
	print( "\nUsing the {$identifier} vendor autoloader ...\n\n" );
	return require $path;
}

function runTestAutoLoader() {

	$mwVendorPath = __DIR__ . '/../../../vendor/autoload.php';
	$localVendorPath = __DIR__ . '/../vendor/autoload.php';

	if ( is_readable( $localVendorPath ) ) {
		$autoLoader = registerAutoloaderPath( 'local', $localVendorPath );
	} elseif ( is_readable( $mwVendorPath ) ) {
		$autoLoader = registerAutoloaderPath( 'MediaWiki', $mwVendorPath );
	}

	if ( !$autoLoader instanceof \Composer\Autoload\ClassLoader ) {
		return false;
	}

	$classAutoLoader = new \SMW\Tests\ClassAutoLoader( $autoLoader );
	$classAutoLoader->addClassesToRunFullTest();

	return true;
}

if ( !runTestAutoLoader() ) {
	die( 'The required test autoloader was not accessible' );
}
