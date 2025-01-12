<?php

namespace SMW\Tests\Exporter\Controller;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;

/**
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 5.0-alpha
 *
 * @author freephile
 */
class GetDBHandleTest extends TestCase {

	/**
	 * @covers \SMW\Exporter\SMWExportController::getDBHandle
	 */
	public function testGetDBHandle() {
		$mwVersion = MW_VERSION;

		// Store original instance for cleanup
		$originalServices = MediaWikiServices::getInstance();

		if ( version_compare( $mwVersion, '1.42', '>=' ) ) {
			$connectionProvider = $this->createMock( \Wikimedia\Rdbms\ConnectionProvider::class );
			$replicaDatabase = $this->createMock( \Wikimedia\Rdbms\IDatabase::class );
			$connectionProvider->method( 'getReplicaDatabase' )->willReturn( $replicaDatabase );

			$mediaWikiServices = $this->createMock( MediaWikiServices::class );
			$mediaWikiServices->method( 'getConnectionProvider' )->willReturn( $connectionProvider );

			MediaWikiServices::setInstanceForTesting( $mediaWikiServices );

			$this->assertSame( $replicaDatabase, SMWExportController::getDBHandle() );

			// Test error condition
			$connectionProvider = $this->createMock( \Wikimedia\Rdbms\ConnectionProvider::class );
			$connectionProvider->method( 'getReplicaDatabase' )
				->willThrowException( new \RuntimeException( 'Connection failed' ) );

			$mediaWikiServices = $this->createMock( MediaWikiServices::class );
			$mediaWikiServices->method( 'getConnectionProvider' )
				->willReturn( $connectionProvider );

			MediaWikiServices::setInstanceForTesting( $mediaWikiServices );

			$this->expectException( \RuntimeException::class );
			SMWExportController::getDBHandle();
		} else {
			$dbLoadBalancer = $this->createMock( \Wikimedia\Rdbms\LoadBalancer::class );
			$replicaDatabase = $this->createMock( \Wikimedia\Rdbms\IDatabase::class );
			$dbLoadBalancer->expects( $this->once() )
				->method( 'getConnection' )
				->with( DB_REPLICA )
				->willReturn( $replicaDatabase );

			$mediaWikiServices = $this->createMock( MediaWikiServices::class );
			$mediaWikiServices->method( 'getDBLoadBalancer' )->willReturn( $dbLoadBalancer );

			MediaWikiServices::setInstanceForTesting( $mediaWikiServices );

			$this->assertSame( $replicaDatabase, SMWExportController::getDBHandle() );
		}

		// Cleanup
		MediaWikiServices::setInstanceForTesting( $originalServices );
	}
}
