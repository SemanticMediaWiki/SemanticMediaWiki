<?php

use SMW\MediaWiki\Connection\Sequence;
use SMW\MediaWiki\Connection\CleanUpTables;
use SMW\ApplicationFactory;
use SMW\SQLStore\SQLStore;

if ( PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg' ) {
	die( 'Not an entry point' );
}

error_reporting( -1 );
ini_set( 'display_errors', '1' );

$autoloader = require SMW_PHPUNIT_AUTOLOADER_FILE;

$autoloader->addPsr4( 'SMW\\Test\\', __DIR__ . '/phpunit' );
$autoloader->addPsr4( 'SMW\\Tests\\', __DIR__ . '/phpunit' );

$autoloader->addClassMap( [
	'SMW\Tests\DataItemTest'                     => __DIR__ . '/phpunit/includes/dataitems/DataItemTest.php',
	'SMW\Maintenance\RebuildConceptCache'        => __DIR__ . '/../maintenance/rebuildConceptCache.php',
	'SMW\Maintenance\RebuildData'                => __DIR__ . '/../maintenance/rebuildData.php',
	'SMW\Maintenance\RebuildPropertyStatistics'  => __DIR__ . '/../maintenance/rebuildPropertyStatistics.php',
	'SMW\Maintenance\RebuildFulltextSearchTable' => __DIR__ . '/../maintenance/rebuildFulltextSearchTable.php',
	'SMW\Maintenance\DumpRdf'                    => __DIR__ . '/../maintenance/dumpRDF.php',
	'SMW\Maintenance\SetupStore'                 => __DIR__ . '/../maintenance/setupStore.php',
	'SMW\Maintenance\UpdateEntityCollation'      => __DIR__ . '/../maintenance/updateEntityCollation.php',
	'SMW\Maintenance\RemoveDuplicateEntities'    => __DIR__ . '/../maintenance/removeDuplicateEntities.php'
] );

define( 'SMW_PHPUNIT_DIR', __DIR__ . '/phpunit' );
define( 'SMW_PHPUNIT_TABLE_PREFIX', 'sunittest_' );

/**
 * Register a shutdown function the invoke a final clean-up
 */
register_shutdown_function( function() {

	if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
		return;
	}

	$connectionManager = ApplicationFactory::getInstance()->getConnectionManager();
	$connection = $connectionManager->getConnection( 'mw.db' );

	// Reset any sequence modified during the test
	$sequence = new Sequence(
		$connection
	);

	try {
		$sequence->tablePrefix( '' );
		$sequence->restart( SQLStore::ID_TABLE, 'smw_id' );
	} catch( \Wikimedia\Rdbms\DBConnectionError $e ) {
		return;
	}

	$cleanUpTables = new CleanUpTables(
		$connection
	);

	$cleanUpTables->dropTables( SMW_PHPUNIT_TABLE_PREFIX );
} );
