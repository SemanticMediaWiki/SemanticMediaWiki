<?php

namespace SMW\Tests\Elastic\Connection;

use SMW\Elastic\Connection\Client;
use SMW\Options;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\Elastic\Connection\Client
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ClientTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $elasticClient;
	private $lockManager;

	protected function setUp() {

		if ( !class_exists( '\Elasticsearch\Client' ) ) {
			$this->markTestSkipped( "elasticsearch-php dependency is not available." );
		}

		$this->elasticClient = $this->getMockBuilder( '\Elasticsearch\Client' )
			->disableOriginalConstructor()
			->getMock();

		$this->lockManager = $this->getMockBuilder( '\SMW\Elastic\Connection\LockManager' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			Client::class,
			new Client( $this->elasticClient, $this->lockManager )
		);
	}

	public function testBulkOnIllegalArgumentErrorThrowsReplicationException() {

		$options = new Options (
			[
				'replication' => [
					'throw.exception.on.illegal.argument.error' => true
				]
			]
		);

		$response = '{"took":67,"errors":true,"items":[{"index":{"_index":"smw-data-test","_type":"data","_id":"14099","status":400,"error":{"type":"illegal_argument_exception","reason":"Limit of total fields [20] in index [smw-data-test] has been exceeded"}}}]}';

		$this->elasticClient->expects( $this->once() )
			->method( 'bulk' )
			->will( $this->returnValue( json_decode( $response, true ) ) );

		$instance = new Client(
			$this->elasticClient,
			$this->lockManager,
			$options
		);

		$params = [
			'index' => 'foo'
		];

		$this->setExpectedException( '\SMW\Elastic\Exception\ReplicationException' );
		$instance->bulk( $params );
	}

}
