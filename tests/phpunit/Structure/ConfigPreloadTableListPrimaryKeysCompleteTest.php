<?php

namespace SMW\Tests\Structure;

use SMW\Services\ServicesFactory;
use SMW\SQLStore\TableBuilder\TableSchemaManager;
use SMW\SQLStore\SQLStore;
use ReflectionClass;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class ConfigPreloadTableListPrimaryKeysCompleteTest extends \PHPUnit_Framework_TestCase {

	const FILENAME = 'db-primary-keys.php';

	public function testCheckTableList() {
		$store = ServicesFactory::getInstance()->getStore( SQLStore::class );
		$file = $GLOBALS['smwgDir'] . '/data/config/' . self::FILENAME;

		if ( !is_readable( $file ) ) {
			throw new RuntimeException( "Unable to access $file." );
		}

		require_once $file;

		$reflectionClass = new ReflectionClass( '\ConfigPreloadPrimaryKeyTableMutator' );
		$tableKeys = $reflectionClass->getConstant( 'PRIMARY_KEYS' );

		$unlistedTables = [];
		$connection = $store->getConnection( DB_PRIMARY );

		$tableSchemaManager = new TableSchemaManager(
			$store
		);

		foreach ( $tableSchemaManager->getTables() as $table ) {
			$tableName = $table->getName();

			if ( isset( $tableKeys[$tableName] ) ) {
				continue;
			}

			$unlistedTables[] = $tableName;
		}

		$this->assertEmpty(
			$unlistedTables,
			'Some table definition(s) are missing from the ' . self::FILENAME . ' list including: ' .
			implode( ', ', $unlistedTables )
		);
	}

}
