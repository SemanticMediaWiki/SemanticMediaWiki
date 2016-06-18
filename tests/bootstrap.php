<?php

if ( PHP_SAPI !== 'cli' ) {
	die( 'Not an entry point' );
}

error_reporting( -1 );
ini_set( 'display_errors', '1' );

$autoloader = require __DIR__ . '/autoloader.php';

$autoloader->addPsr4( 'SMW\\Test\\', __DIR__ . '/phpunit' );
$autoloader->addPsr4( 'SMW\\Tests\\', __DIR__ . '/phpunit' );

$autoloader->addClassMap( array(
	'SMW\Tests\DataItemTest'                     => __DIR__ . '/phpunit/includes/dataitems/DataItemTest.php',
	'SMW\Tests\Reporter\MessageReporterTestCase' => __DIR__ . '/phpunit/includes/Reporter/MessageReporterTestCase.php',
	'SMW\Maintenance\RebuildConceptCache'        => __DIR__ . '/../maintenance/rebuildConceptCache.php',
	'SMW\Maintenance\RebuildData'                => __DIR__ . '/../maintenance/rebuildData.php',
	'SMW\Maintenance\RebuildPropertyStatistics'  => __DIR__ . '/../maintenance/rebuildPropertyStatistics.php',
	'SMW\Maintenance\DumpRdf'                    => __DIR__ . '/../maintenance/dumpRDF.php',
	'SMW\Maintenance\SetupStore'                 => __DIR__ . '/../maintenance/setupStore.php'
) );
