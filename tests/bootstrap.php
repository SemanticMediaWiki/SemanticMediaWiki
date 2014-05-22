<?php

if ( php_sapi_name() !== 'cli' ) {
	die( 'Not an entry point' );
}

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'MediaWiki is not available for the test environment' );
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

	$autoLoader->addPsr4( 'SMW\\Test\\', __DIR__ . '/phpunit' );
	$autoLoader->addPsr4( 'SMW\\Tests\\', __DIR__ . '/phpunit' );

	// FIXME
	$autoLoader->addClassMap( array(
		'SMW\Tests\DataItemTest'                     => __DIR__ . '/phpunit/includes/dataitems/DataItemTest.php',
		'SMW\Tests\Reporter\MessageReporterTestCase' => __DIR__ . '/phpunit/includes/Reporter/MessageReporterTestCase.php',
		'SMW\Maintenance\RebuildConceptCache'        => __DIR__ . '/../maintenance/rebuildConceptCache.php',
		'SMW\Maintenance\RebuildData'                => __DIR__ . '/../maintenance/rebuildData.php',
		'SMW\Maintenance\RebuildPropertyStatistics'  => __DIR__ . '/../maintenance/rebuildPropertyStatistics.php'
	) );

	return true;
}

if ( !runTestAutoLoader() ) {
	die( 'The required test autoloader was not accessible' );
}
