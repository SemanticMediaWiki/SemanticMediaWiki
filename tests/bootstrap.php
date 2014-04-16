<?php

if ( php_sapi_name() !== 'cli' ) {
	die( 'Not an entry point' );
}

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'MediaWiki is not available for the test environment' );
}

function registerAutoloaderPath( $identifier, $path ) {
	print( "\nUsing the {$identifier} vendor autoloader ...\n" );
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

	$autoLoader->addPsr4( 'SMW\\Test\\', __DIR__ . '/phpunit' );

	// FIXME
	$autoLoader->addClassMap( array(
		'SMW\Tests\DataItemTest'                    => __DIR__ . '/phpunit/includes/dataitems/DataItemTest.php',
		'SMW\Maintenance\RebuildConceptCache'       => __DIR__ . '/../maintenance/rebuildConceptCache.php',
		'SMW\Maintenance\RebuildData'               => __DIR__ . '/../maintenance/rebuildData.php',
		'SMW\Maintenance\RebuildPropertyStatistics' => __DIR__ . '/../maintenance/rebuildPropertyStatistics.php'
	) );

	$autoLoader->addPsr4( 'SMW\\Tests\\Integration\\', __DIR__ . '/phpunit/Integration' );
	$autoLoader->addPsr4( 'SMW\\Tests\\Regression\\', __DIR__ . '/phpunit/Regression' );
	$autoLoader->addPsr4( 'SMW\\Tests\\System\\', __DIR__ . '/phpunit/System' );
	$autoLoader->addPsr4( 'SMW\\Tests\\Util\\', __DIR__ . '/phpunit/Util' );

	return true;
}

if ( !runTestAutoLoader() ) {
	die( 'The required test autoloader was not accessible' );
}
