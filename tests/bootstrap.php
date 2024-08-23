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

$IP = getenv( 'MW_INSTALL_PATH' ) !== false ? getenv( 'MW_INSTALL_PATH' ) : __DIR__ . '/../../..';

require_once $IP . '/tests/phpunit/bootstrap.php';

// Convenience function for extensions depending on a SMW specific
// test infrastructure
if ( !defined( 'SMW_PHPUNIT_AUTOLOADER_FILE' ) ) {
	define( 'SMW_PHPUNIT_AUTOLOADER_FILE', __DIR__ . '/autoloader.php' );
}

define( 'SMW_PHPUNIT_DIR', __DIR__ . '/phpunit' );
define( 'SMW_PHPUNIT_TABLE_PREFIX', 'sunittest_' );
// define( 'SMW_PHPUNIT_TABLE_PREFIX', '' );

/**
 * Register a shutdown function the invoke a final clean-up
 */
register_shutdown_function( function () {
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
