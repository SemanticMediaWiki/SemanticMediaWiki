<?php

namespace SMW\Tests\Elastic\Lookup;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SMW\DataItems\Property;
use SMW\Elastic\Connection\Client;
use SMW\Elastic\Lookup\ProximityPropertyValueLookup;
use SMW\RequestOptions;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\Elastic\Lookup\ProximityPropertyValueLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ProximityPropertyValueLookupTest extends TestCase {

	private $logger;
	private $idTable;
	private $store;
	private $elasticClient;

	protected function setUp(): void {
		$this->logger = $this->getMockBuilder( LoggerInterface::class )
			->disableOriginalConstructor()
			->getMock();

		$this->idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'getSMWPropertyID' ] )
			->getMock();

		$this->idTable->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->willReturnOnConsecutiveCalls( 42, 1001, 9000, 110001 );

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'getConnection' ] )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->willReturn( $this->idTable );

		$this->elasticClient = $this->getMockBuilder( Client::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $this->elasticClient );
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ProximityPropertyValueLookup::class,
			new ProximityPropertyValueLookup( $this->store )
		);
	}

	public function testLookup_AnyValue() {
		$params = [
			'index' => null,
			'body' => [
				'_source' => [ 'P:42.wpgField' ],
				'from' => 0,
				'size' => 500,
				'query' => [
					'exists' => [ 'field' => 'P:42.wpgField' ]
				]
			]
		];

		$this->elasticClient->expects( $this->once() )
			->method( 'search' )
			->with( $params )
			->willReturn( [ [], [] ] );

		$instance = new ProximityPropertyValueLookup(
			$this->store
		);

		$instance->lookup( new Property( 'Foo' ), '', new RequestOptions() );
	}

}
