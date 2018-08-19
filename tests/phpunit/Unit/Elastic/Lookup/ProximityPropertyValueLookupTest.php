<?php

namespace SMW\Tests\Elastic\Lookup;

use SMW\Elastic\Lookup\ProximityPropertyValueLookup;
use SMW\Tests\PHPUnitCompat;
use SMW\DIProperty;
use SMW\RequestOptions;

/**
 * @covers \SMW\Elastic\Lookup\ProximityPropertyValueLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ProximityPropertyValueLookupTest extends \PHPUnit_Framework_TestCase {

	use PHPUnitCompat;

	private $logger;
	private $store;
	private $elasticClient;

	protected function setUp() {

		$this->logger = $this->getMockBuilder( '\Psr\Log\LoggerInterface' )
			->disableOriginalConstructor()
			->getMock();

		$this->idTable = $this->getMockBuilder( '\stdClass' )
			->disableOriginalConstructor()
			->setMethods( [ 'getSMWPropertyID' ] )
			->getMock();

		$this->idTable->expects( $this->any() )
			->method( 'getSMWPropertyID' )
			->will( $this->onConsecutiveCalls( 42, 1001, 9000, 110001 ) );

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( [ 'getObjectIds', 'getConnection' ] )
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $this->idTable ) );

		$this->elasticClient = $this->getMockBuilder( '\SMW\Elastic\Connection\Client' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $this->elasticClient ) );
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
			'type' => 'data',
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
			->with( $this->equalTo( $params ) );

		$instance = new ProximityPropertyValueLookup(
			$this->store
		);

		$instance->lookup( new DIProperty( 'Foo' ), '', new RequestOptions() );
	}

}
