<?php

namespace SMW\Tests\Query;

use SMW\Query\QueryCreator;
use SMW\ApplicationFactory;

/**
 * @covers SMW\Query\QueryCreator
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
			'SMW\Query\QueryCreator',
			new QueryCreator( $queryFactory )
		);
	}

	/**
	 * @dataProvider queryStringProvider
	 */
	public function testCreate( $queryString, $configuration, $expected ) {

		$instance = new QueryCreator(
			ApplicationFactory::getInstance()->getQueryFactory()
		);

		$query = $instance->setConfiguration( $configuration )->create( $queryString );

		$this->assertInstanceOf(
			'\SMWQuery',
			$query
		);

		$this->assertSame(
			$expected,
			$query->getAsString()
		);
	}

	public function queryStringProvider() {

		$provider[] = array(
			'[[Foo::Bar]]',
			array(
				'limit'  => 42,
				'offset' => 12
			),
			'[[Foo::Bar]]|limit=42|offset=12|mainlabel='
		);

		$provider[] = array(
			'[[Foo::Bar]]',
			array(
				'querySource' => 'foobar',
				'mainLabel'   => 'Some'
			),
			'[[Foo::Bar]]|limit=50|offset=0|mainlabel=Some|source=foobar'
		);

		$provider[] = array(
			'[[Foo::Bar]]',
			array(
				'sort'  => array( '', 'SomeA', 'SomeB' ),
				'order' => array( 'desc', 'random', 'asc' )
			),
			'[[Foo::Bar]]|limit=50|offset=0|mainlabel=|sort=SomeA,SomeB|order=random,asc'
		);

		$provider[] = array(
			'[[Foo::Bar]]',
			array(
				'sort'  => array( ',' )
			),
			'[[Foo::Bar]]|limit=50|offset=0|mainlabel=|sort=,|order=asc'
		);

		return $provider;
	}

}
