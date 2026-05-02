<?php

namespace SMW\Tests\Unit\SQLStore\TableBuilder\Examiner;

use PHPUnit\Framework\TestCase;
use SMW\Maintenance\populateHashField;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\SQLStore;
use SMW\SQLStore\TableBuilder\Examiner\HashField;
use SMW\Tests\TestEnvironment;
use Wikimedia\Rdbms\DBError;
use Wikimedia\Rdbms\ResultWrapper;

/**
 * @covers \SMW\SQLStore\TableBuilder\Examiner\HashField
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class HashFieldTest extends TestCase {

	private $spyMessageReporter;
	private $store;
	private $populateHashField;
	private $connection;

	protected function setUp(): void {
		parent::setUp();
		$this->spyMessageReporter = TestEnvironment::getUtilityFactory()->newSpyMessageReporter();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->method( 'selectField' )
			->willReturn( 0 );

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->method( 'getConnection' )
			->willReturn( $this->connection );

		$this->populateHashField = $this->getMockBuilder( populateHashField::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			HashField::class,
			new HashField( $this->store )
		);
	}

	public function testCheck_Populate() {
		$resultWrapper = $this->getMockBuilder( ResultWrapper::class )
			->disableOriginalConstructor()
			->getMock();

		$resultWrapper->expects( $this->once() )
			->method( 'numRows' )
			->willReturn( HashField::threshold() - 1 );

		$this->populateHashField->expects( $this->atLeastOnce() )
			->method( 'populate' );

		$this->populateHashField->expects( $this->once() )
			->method( 'fetchRows' )
			->willReturn( $resultWrapper );

		$instance = new HashField(
			$this->store,
			$this->populateHashField
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->check();

		$this->assertStringContainsString(
			'Checking smw_hash field consistency',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testMigrateHexHashes_RunsWhenCountExceedsThreshold() {
		// Regression test for issue #6715: when more than threshold hex
		// hashes exist, the SQL conversion must still run — otherwise the
		// subsequent ALTER TABLE BINARY(20) truncates the 40-byte values
		// and the upgrade fails.
		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->method( 'selectField' )
			->willReturn( HashField::threshold() + 1 );

		$this->connection->method( 'tableName' )
			->willReturn( 'smw_object_ids' );

		$this->connection->method( 'getType' )
			->willReturn( 'mysql' );

		$this->connection->expects( $this->once() )
			->method( 'query' )
			->with( $this->stringContains( 'UNHEX(smw_hash)' ) );

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = new HashField( $this->store );
		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->migrateHexHashes();

		$this->assertStringContainsString(
			'converting hex hashes to binary',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

	public function testMigrateHexHashes_EmitsRecoveryHintOnDBError() {
		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->method( 'selectField' )
			->willReturn( 1 );

		$this->connection->method( 'tableName' )
			->willReturn( 'smw_object_ids' );

		$this->connection->method( 'getType' )
			->willReturn( 'mysql' );

		$dbError = $this->getMockBuilder( DBError::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connection->method( 'query' )
			->willThrowException( $dbError );

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->method( 'getConnection' )
			->willReturn( $this->connection );

		$instance = new HashField( $this->store );
		$instance->setMessageReporter( $this->spyMessageReporter );

		try {
			$instance->migrateHexHashes();
			$this->fail( 'Expected DBError to be re-thrown' );
		} catch ( DBError $e ) {
			// expected
		}

		$messages = $this->spyMessageReporter->getMessagesAsString();
		$this->assertStringContainsString( 'hex-to-binary conversion failed', $messages );
		$this->assertStringContainsString( 're-run `update.php`', $messages );
	}

	public function testMigrateHexHashes_NoOpWhenCountIsZero() {
		$this->connection->expects( $this->never() )
			->method( 'query' );

		$instance = new HashField( $this->store );
		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->migrateHexHashes();
	}

	public function testCheck_Incomplete() {
		$resultWrapper = $this->getMockBuilder( ResultWrapper::class )
			->disableOriginalConstructor()
			->getMock();

		$resultWrapper->expects( $this->once() )
			->method( 'numRows' )
			->willReturn( HashField::threshold() + 1 );

		$this->populateHashField->expects( $this->atLeastOnce() )
			->method( 'setComplete' );

		$this->populateHashField->expects( $this->once() )
			->method( 'fetchRows' )
			->willReturn( $resultWrapper );

		$instance = new HashField(
			$this->store,
			$this->populateHashField
		);

		$instance->setMessageReporter( $this->spyMessageReporter );
		$instance->check();

		$this->assertStringContainsString(
			'Checking smw_hash field consistency',
			$this->spyMessageReporter->getMessagesAsString()
		);
	}

}
