<?php

namespace SMW\Tests\Exporter\Controller;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;

/**
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

		if ( version_compare( $mwVersion, '1.42', '>=' ) ) {
			$connectionProvider = $this->createMock( \Wikimedia\Rdbms\ConnectionProvider::class );
			$replicaDatabase = $this->createMock( \Wikimedia\Rdbms\IDatabase::class );
			$connectionProvider->method( 'getReplicaDatabase' )->willReturn( $replicaDatabase );

			$mediaWikiServices = $this->createMock( MediaWikiServices::class );
			$mediaWikiServices->method( 'getConnectionProvider' )->willReturn( $connectionProvider );

			MediaWikiServices::setInstanceForTesting( $mediaWikiServices );

			$this->assertSame( $replicaDatabase, SMWExportController::getDBHandle() );
		} else {
			$dbLoadBalancer = $this->createMock( \Wikimedia\Rdbms\LoadBalancer::class );
			$replicaDatabase = $this->createMock( \Wikimedia\Rdbms\IDatabase::class );
			$dbLoadBalancer->method( 'getConnection' )->with( DB_REPLICA )->willReturn( $replicaDatabase );

			$mediaWikiServices = $this->createMock( MediaWikiServices::class );
			$mediaWikiServices->method( 'getDBLoadBalancer' )->willReturn( $dbLoadBalancer );

			MediaWikiServices::setInstanceForTesting( $mediaWikiServices );

			$this->assertSame( $replicaDatabase, SMWExportController::getDBHandle() );
		}
	}
}
