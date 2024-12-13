<?php

use SMW\MediaWiki\Connection\Sequence;
use SMW\MediaWiki\Connection\CleanUpTables;
use SMW\Services\ServicesFactory as ApplicationFactory;
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
	'SMW\Tests\PHPUnitCheckRunnerTest'           => __DIR__ . '/phpunit/PHPUnitCheckRunnerTest.php',
	'SMW\Tests\DataItemTest'                     => __DIR__ . '/phpunit/includes/dataitems/DataItemTest.php',
	'SMW\Maintenance\rebuildConceptCache'        => __DIR__ . '/../maintenance/rebuildConceptCache.php',
	'SMW\Maintenance\rebuildData'                => __DIR__ . '/../maintenance/rebuildData.php',
	'SMW\Maintenance\rebuildPropertyStatistics'  => __DIR__ . '/../maintenance/rebuildPropertyStatistics.php',
	'SMW\Maintenance\rebuildFulltextSearchTable' => __DIR__ . '/../maintenance/rebuildFulltextSearchTable.php',
	'SMW\Maintenance\dumpRDF'                    => __DIR__ . '/../maintenance/dumpRDF.php',
	'SMW\Maintenance\setupStore'                 => __DIR__ . '/../maintenance/setupStore.php',
	'SMW\Maintenance\updateEntityCollation'      => __DIR__ . '/../maintenance/updateEntityCollation.php',
	'SMW\Maintenance\removeDuplicateEntities'    => __DIR__ . '/../maintenance/removeDuplicateEntities.php',
	'SMW\Maintenance\purgeEntityCache'           => __DIR__ . '/../maintenance/purgeEntityCache.php',
	'SMW\Maintenance\updateQueryDependencies'    => __DIR__ . '/../maintenance/updateQueryDependencies.php',
	'SMW\Maintenance\runImport'                  => __DIR__ . '/../maintenance/runImport.php',
	'SMW\Maintenance\disposeOutdatedEntities'    => __DIR__ . '/../maintenance/disposeOutdatedEntities.php',
	'SMW\Maintenance\updateEntityCountMap'       => __DIR__ . '/../maintenance/updateEntityCountMap.php'
] );

define( 'SMW_PHPUNIT_DIR', __DIR__ . '/phpunit' );
// define( 'SMW_PHPUNIT_TABLE_PREFIX', 'sunittest_' );
// define( 'SMW_PHPUNIT_TABLE_PREFIX', '' );

/**
 * Register a shutdown function the invoke a final clean-up
 */
register_shutdown_function( function () {
	if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
		return;
	}

	// $connectionManager = ApplicationFactory::getInstance()->getConnectionManager();
	// $connection = $connectionManager->getConnection( 'mw.db' );

	// // Reset any sequence modified during the test
	// $sequence = new Sequence(
	// 	$connection
	// );

	// try {
	// 	$sequence->tablePrefix( '' );
	// 	$sequence->restart( SQLStore::ID_TABLE, 'smw_id' );
	// } catch( \Wikimedia\Rdbms\DBConnectionError $e ) {
	// 	return;
	// }

	// $cleanUpTables = new CleanUpTables(
	// 	$connection
	// );

	// $cleanUpTables->dropTables( SMW_PHPUNIT_TABLE_PREFIX );
} );
