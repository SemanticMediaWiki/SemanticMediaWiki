<?php

namespace SMW\Tests\MediaWiki\Api\Browse;

use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Api\Browse\ListAugmentor;
use SMW\MediaWiki\Api\Browse\ListLookup;
use SMW\MediaWiki\Connection\Database;
use SMW\SQLStore\SQLStore;

/**
 * @covers \SMW\MediaWiki\Api\Browse\ListLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class ListLookupTest extends TestCase {

	public function testCanConstruct() {
		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$listAugmentor = $this->getMockBuilder( ListAugmentor::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ListLookup::class,
			new ListLookup( $store, $listAugmentor )
		);
	}

	/**
	 * @dataProvider namespaceProvider
	 */
	public function testLookup( $ns, $title, $expected ) {
		$row = new \stdClass;
		$row->smw_title = $title;
		$row->smw_id = 42;

		$listAugmentor = $this->getMockBuilder( ListAugmentor::class )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->willReturn( [ $row ] );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSQLOptions' )
			->willReturn( [] );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$instance = new ListLookup(
			$store,
			$listAugmentor
		);

		$parameters = [
			'ns' => $ns,
			'search' => 'Foo',
			'sort' => true
		];

		$res = $instance->lookup( $parameters );

		$this->assertEquals(
			$expected,
			$res['query']
		);
	}

	public function namespaceProvider() {
		$provider[] = [
			SMW_NS_PROPERTY,
			'Foo',
			[
				'Foo' => [
					'id' => 42,
					'label' => 'Foo',
					'key' => 'Foo'
				]
			]
		];

		$provider[] = [
			NS_CATEGORY,
			'Foo',
			[
				'Foo' => [
					'id' => 42,
					'label' => 'Foo',
					'key' => 'Foo'
				]
			]
		];

		$provider[] = [
			SMW_NS_CONCEPT,
			'Foo',
			[
				'Foo' => [
					'id' => 42,
					'label' => 'Foo',
					'key' => 'Foo'
				]
			]
		];

		return $provider;
	}

}
