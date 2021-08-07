<?php

namespace SMW\Tests\MediaWiki\Api\Browse;

use SMW\MediaWiki\Api\Browse\ArticleLookup;

/**
 * @covers \SMW\MediaWiki\Api\Browse\ArticleLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ArticleLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$articleAugmentor = $this->getMockBuilder( '\SMW\MediaWiki\Api\Browse\ArticleAugmentor' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ArticleLookup::class,
			new ArticleLookup( $connection, $articleAugmentor )
		);
	}

	/**
	 * @dataProvider articleSearchProvider
	 */
	public function testLookup( $search, $row, $condition, $expected ) {

		$articleAugmentor = $this->getMockBuilder( '\SMW\MediaWiki\Api\Browse\ArticleAugmentor' )
			->disableOriginalConstructor()
			->getMock();

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'addQuotes' )
			->will( $this->returnArgument( 0 ) );

		$connection->expects( $this->atLeastOnce() )
			->method( 'select' )
			->with(
				$this->anyThing(),
				$this->anyThing(),
				$this->stringContains( $condition ) )
			->will( $this->returnValue( [ $row ] ) );

		$instance = new ArticleLookup(
			$connection,
			$articleAugmentor
		);

		$parameters = [
			'search' => $search
		];

		$res = $instance->lookup( $parameters );

		$this->assertEquals(
			$res['query'],
			$expected
		);
	}

	public function articleSearchProvider() {

		$row = new \stdClass;
		$row->page_title = 'Foo';
		$row->page_id = 42;
		$row->page_namespace = 0;

		$provider[] = [
			'Foo',
			$row,
			'page_title LIKE %Foo% ESCAPE ` OR page_title LIKE %Foo% ESCAPE ` OR page_title LIKE %FOO% ESCAPE ` OR page_title LIKE %foo% ESCAPE `',
			[
				'Foo#0' => [
					'id' => 42,
					'label' => 'Foo',
					'key' => 'Foo',
					'ns' => 0
				]
			]
		];

		$row = new \stdClass;
		$row->page_title = 'Foo';
		$row->page_id = 42;
		$row->page_namespace = 12;

		$provider[] = [
			'Help:Fo o',
			$row,
			'page_namespace=12 AND (page_title LIKE %Fo`_o% ESCAPE ` OR page_title LIKE %Fo`_o% ESCAPE ` OR page_title LIKE %FO`_O% ESCAPE ` OR page_title LIKE %fo`_o% ESCAPE `)',
			[
				'Foo#12' => [
					'id' => 42,
					'label' => 'Foo',
					'key' => 'Foo',
					'ns' => 12
				]
			]
		];

		return $provider;
	}

}
