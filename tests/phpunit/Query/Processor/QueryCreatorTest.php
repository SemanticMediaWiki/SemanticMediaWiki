<?php

namespace SMW\Tests\Query\Processor;

use PHPUnit\Framework\TestCase;
use SMW\Query\Processor\QueryCreator;
use SMW\Query\Query;
use SMW\QueryFactory;
use SMW\Services\ServicesFactory as ApplicationFactory;

/**
 * @covers SMW\Query\Processor\QueryCreator
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class QueryCreatorTest extends TestCase {

	public function testCanConstruct() {
		$queryFactory = $this->getMockBuilder( QueryFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			QueryCreator::class,
			new QueryCreator( $queryFactory )
		);
	}

	/**
	 * @dataProvider queryStringProvider
	 */
	public function testCreate( $queryString, $params, $expected ) {
		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$query = $instance->create( $queryString, $params );

		$this->assertInstanceOf(
			Query::class,
			$query
		);

		$this->assertSame(
			$expected,
			$query->toString()
		);
	}

	public function queryStringProvider() {
		$provider[] = [
			'[[Foo::Bar]]',
			[
				'limit'  => 42,
				'offset' => 12
			],
			'[[Foo::Bar]]|limit=42|offset=12|mainlabel='
		];

		$provider[] = [
			'[[Foo::Bar]]',
			[
				'source'    => 'foobar',
				'mainLabel' => 'Some'
			],
			'[[Foo::Bar]]|limit=50|offset=0|mainlabel=Some|source=foobar'
		];

		$provider[] = [
			'[[Foo::Bar]]',
			[
				'sort'  => [ '', 'SomeA', 'SomeB' ],
				'order' => [ 'desc', 'random', 'asc' ]
			],
			'[[Foo::Bar]]|limit=50|offset=0|mainlabel=|sort=SomeA,SomeB|order=random,asc'
		];

		$provider[] = [
			'[[Foo::Bar]]',
			[
				'sort'  => [ ',' ]
			],
			'[[Foo::Bar]]|limit=50|offset=0|mainlabel=|sort=,|order=asc'
		];

		return $provider;
	}

}
