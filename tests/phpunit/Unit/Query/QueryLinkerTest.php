<?php

namespace SMW\Tests\Unit\Query;

use PHPUnit\Framework\TestCase;
use SMW\Formatters\Infolink;
use SMW\Query\Query;
use SMW\Query\QueryLinker;

/**
 * @covers SMW\Query\QueryLinker
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class QueryLinkerTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			QueryLinker::class,
			new QueryLinker()
		);
	}

	public function testGet() {
		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getExtraPrintouts' )
			->willReturn( [] );

		$query->expects( $this->once() )
			->method( 'getSortKeys' )
			->willReturn( [] );

		$parameters = [
			'Foo' => 'Bar',
			'Foobar'
		];

		$this->assertInstanceOf(
			Infolink::class,
			QueryLinker::get( $query, $parameters )
		);
	}

	/**
	 * @dataProvider sortOrderProvider
	 */
	public function testSort_PredefinedProperty( $sortKeys, $expected ) {
		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->once() )
			->method( 'getExtraPrintouts' )
			->willReturn( [] );

		$query->expects( $this->once() )
			->method( 'getSortKeys' )
			->willReturn( $sortKeys );

		$link = QueryLinker::get( $query );
		$link->setCompactLink( false );

		$this->assertStringContainsString(
			$expected,
			$link->getLocalURL()
		);
	}

	public function sortOrderProvider() {
		yield [
			[ '_MDAT' => 'DESC' ],
			'&order=desc&sort=Modification%20date'
		];

		yield [
			[ '' => 'ASC' ],
			'&mainlabel=&source=&offset='
		];

		yield [
			[ 'Foo_bar' => 'ASC' ],
			'&mainlabel=&source=&offset=&order=asc&sort=Foo%20bar'
		];

		yield [
			[ '' => 'ASC', 'Foo_bar' => 'DESC' ],
			'&mainlabel=&source=&offset=&order=asc%2Cdesc&sort=%2CFoo%20bar'
		];
	}

}
