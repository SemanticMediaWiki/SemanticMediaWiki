<?php

namespace SMW\Tests\MediaWiki\Api\Browse;

use SMW\MediaWiki\Api\Browse\ListAugmentor;

/**
 * @covers \SMW\MediaWiki\Api\Browse\ListAugmentor
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ListAugmentorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ListAugmentor::class,
			new ListAugmentor( $store )
		);
	}

	public function testAugmentOnDescription() {

		$res = [
			'query' => [
				'Foo' => [
					'id' => 42,
					'label' => 'Foo',
					'key' => 'Foo'
				]
			],
			'query-continue-offset' => 0,
			'version' => 1,
			'meta' => [
				'type'  => 'property',
				'limit' => 50,
				'count' => 1
			]
		];

		$parameters = [
			'description' => true,
			'lang' => [ 'en', 'ja' ]
		];

		$expected = [
			'Foo' => [
				'label' => 'Foo',
				'key' => 'Foo',
				'description' => [
					'en' => '',
					'ja' => ''
				]
			]
		];

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ListAugmentor(
			$store
		);

		$instance->augment( $res, $parameters );

		$this->assertEquals(
			$res['query'],
			$expected
		);
	}

	public function testAugmentOnPrefLabel() {

		$res = [
			'query' => [
				'Foo' => [
					'id' => 42,
					'label' => 'Foo',
					'key' => 'Foo'
				]
			],
			'query-continue-offset' => 0,
			'version' => 1,
			'meta' => [
				'type'  => 'property',
				'limit' => 50,
				'count' => 1
			]
		];

		$parameters = [
			'prefLabel' => true,
			'lang' => [ 'en', 'ja' ]
		];

		$expected = [
			'Foo' => [
				'label' => 'Foo',
				'key' => 'Foo',
				'prefLabel' => [
					'en' => '',
					'ja' => ''
				]
			]
		];

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ListAugmentor(
			$store
		);

		$instance->augment( $res, $parameters );

		$this->assertEquals(
			$res['query'],
			$expected
		);
	}

	public function testAugmentOnUsageCount() {

		$res = [
			'query' => [
				'Foo' => [
					'id' => 42,
					'label' => 'Foo',
					'key' => 'Foo'
				]
			],
			'query-continue-offset' => 0,
			'version' => 1,
			'meta' => [
				'type'  => 'property',
				'limit' => 50,
				'count' => 1
			]
		];

		$parameters = [
			'usageCount' => true
		];

		$expected = [
			'Foo' => [
				'label' => 'Foo',
				'key' => 'Foo',
				'usageCount' => 1111
			]
		];

		$row = new \stdClass;
		$row->usage_count = 1111;

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->will( $this->returnValue( $row ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$instance = new ListAugmentor(
			$store
		);

		$instance->augment( $res, $parameters );

		$this->assertEquals(
			$res['query'],
			$expected
		);
	}

}
