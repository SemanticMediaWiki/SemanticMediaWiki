<?php

namespace SMW\Tests\Unit\MediaWiki\Deferred;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\Deferred\HashFieldUpdate;
use SMW\Tests\TestEnvironment;
use SMW\Tests\Unit\MediaWiki\Connection\MockWriteQueryBuilderTrait;

/**
 * @covers \SMW\MediaWiki\Deferred\HashFieldUpdate
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class HashFieldUpdateTest extends TestCase {

	use MockWriteQueryBuilderTrait;

	private $testEnvironment;
	private $connection;
	private $spyLogger;

	protected function setUp(): void {
		parent::setUp();
		$this->testEnvironment = new TestEnvironment();

		$this->spyLogger = $this->testEnvironment->getUtilityFactory()->newSpyLogger();

		$this->connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			HashFieldUpdate::class,
			new HashFieldUpdate( $this->connection, 42, '__hash__' )
		);
	}

	public function testAddUpdate() {
		$tables = [];
		$sets = [];
		$wheres = [];
		$updateBuilder = $this->createMockUpdateQueryBuilder( $tables, $sets, $wheres );

		$this->connection->expects( $this->once() )
			->method( 'newUpdateQueryBuilder' )
			->willReturn( $updateBuilder );

		HashFieldUpdate::$isCommandLineMode = true;
		HashFieldUpdate::addUpdate( $this->connection, 1001, '' );

		$this->assertSame( [ 'smw_hash' => '' ], $sets[0] );
		$this->assertSame( [ 'smw_id' => 1001 ], $wheres[0] );
	}

	public function testDoUpdate() {
		$tables = [];
		$sets = [];
		$wheres = [];
		$updateBuilder = $this->createMockUpdateQueryBuilder( $tables, $sets, $wheres );

		$this->connection->expects( $this->once() )
			->method( 'newUpdateQueryBuilder' )
			->willReturn( $updateBuilder );

		$instance = new HashFieldUpdate(
			$this->connection,
			42,
			'__hash__'
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->doUpdate();

		$this->assertSame( [ 'smw_hash' => '__hash__' ], $sets[0] );
		$this->assertSame( [ 'smw_id' => 42 ], $wheres[0] );
	}

}
