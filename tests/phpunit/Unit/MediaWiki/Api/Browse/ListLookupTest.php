<?php

namespace SMW\Tests\MediaWiki\Api\Browse;

use SMW\MediaWiki\Api\Browse\ListLookup;

/**
 * @covers \SMW\MediaWiki\Api\Browse\ListLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ListLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$listAugmentor = $this->getMockBuilder( '\SMW\MediaWiki\Api\Browse\ListAugmentor' )
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

		$listAugmentor = $this->getMockBuilder( '\SMW\MediaWiki\Api\Browse\ListAugmentor' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->will( $this->returnValue( [ $row ] ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->atLeastOnce() )
			->method( 'getSQLOptions' )
			->will( $this->returnValue( [] ) );

		$store->expects( $this->atLeastOnce() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

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
			$res['query'],
			$expected
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
