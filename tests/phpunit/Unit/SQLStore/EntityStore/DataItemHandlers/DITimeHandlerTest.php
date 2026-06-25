<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore\DataItemHandlers;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\EntityStore\DataItemHandler;
use SMW\SQLStore\EntityStore\DataItemHandlers\DITimeHandler;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\SQLStore\EntityStore\DataItemHandlers\DITimeHandler
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since   7.1.0
 *
 * @author mwjames
 */
class DITimeHandlerTest extends TestCase {

	private function newHandler( string $dbType ): DITimeHandler {
		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->method( 'getType' )
			->willReturn( $dbType );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->method( 'getConnection' )
			->willReturn( $connection );

		return new DITimeHandler( $store );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			DITimeHandler::class,
			$this->newHandler( 'mysql' )
		);
	}

	public function testGetIndexHintForPropertySubjectsOnMysql() {
		$this->assertSame(
			's_id',
			$this->newHandler( 'mysql' )->getIndexHint( DataItemHandler::IHINT_PSUBJECTS )
		);
	}

	public function testGetIndexHintForPropertySubjectsOnNonMysql() {
		$this->assertSame(
			'',
			$this->newHandler( 'postgres' )->getIndexHint( DataItemHandler::IHINT_PSUBJECTS )
		);
	}

	public function testGetIndexHintForUnknownKeyReturnsNoHint() {
		$this->assertSame(
			'',
			$this->newHandler( 'mysql' )->getIndexHint( 'unknown.key' )
		);
	}

}
