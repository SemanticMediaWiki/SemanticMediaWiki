<?php

namespace SMW\Tests\Query\Processor;

use SMW\ApplicationFactory;
use SMW\Query\Processor\QueryCreator;

/**
 * @covers SMW\Query\Processor\QueryCreator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class QueryCreatorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$queryFactory = $this->getMockBuilder( '\SMW\QueryFactory' )
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
			'\SMWQuery',
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
