<?php

namespace SMW\Tests\Unit\MediaWiki\Api\Browse;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Api\Browse\ListAugmentor;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;
use stdClass;

/**
 * @covers \SMW\MediaWiki\Api\Browse\ListAugmentor
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ListAugmentorTest extends TestCase {

	private $testEnvironment;

	protected function setUp(): void {
		parent::setUp();
		$this->testEnvironment = new TestEnvironment();
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanConstruct() {
		$store = $this->getMockBuilder( SQLStore::class )
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

				$propertySpecificationLookup = $this->getMockBuilder( '\SMW\Property\SpecificationLookup' )
						->disableOriginalConstructor()
						->getMock();

				$propertySpecificationLookup->expects( $this->any() )
						->method( 'getPropertyDescriptionByLanguageCode' )
						->willReturn( '' );

				$this->testEnvironment->registerObject( 'PropertySpecificationLookup', $propertySpecificationLookup );

				$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ListAugmentor(
			$store
		);

		$instance->augment( $res, $parameters );

		$this->assertEquals(
			$expected,
			$res['query']
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

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new ListAugmentor(
			$store
		);

		$instance->augment( $res, $parameters );

		$this->assertEquals(
			$expected,
			$res['query']
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

		$row = new stdClass;
		$row->usage_count = 1111;

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'selectRow' )
			->willReturn( $row );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new ListAugmentor(
			$store
		);

		$instance->augment( $res, $parameters );

		$this->assertEquals(
			$expected,
			$res['query']
		);
	}

}
