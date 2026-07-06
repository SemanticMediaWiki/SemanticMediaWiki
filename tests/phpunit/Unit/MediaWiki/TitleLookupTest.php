<?php

namespace SMW\Tests\Unit\MediaWiki;

use MediaWiki\Title\Title;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\TitleLookup;
use SMW\Tests\Unit\MediaWiki\Connection\MockSelectQueryBuilderTrait;
use stdClass;

/**
 * @covers \SMW\MediaWiki\TitleLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 1.9.2
 *
 * @author mwjames
 */
class TitleLookupTest extends TestCase {

	use MockSelectQueryBuilderTrait;

	public function testCanConstruct() {
		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			TitleLookup::class,
			new TitleLookup( $database )
		);
	}

	public function testSelectAllOnCategoryNamespace() {
		$row = new stdClass;
		$row->cat_title = 'Foo';

		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$whereConditions = [];
		$capturedSelects = [];
		$capturedTables = [];
		$selectBuilder = $this->createMockSelectQueryBuilder(
			[ $row ], $whereConditions, $capturedSelects, $capturedTables
		);
		$database->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$instance = new TitleLookup( $database );

		$this->assertArrayOfTitles(
			$instance->setNamespace( NS_CATEGORY )->selectAll()
		);

		$this->assertSame( [ 'category' ], $capturedTables );
	}

	public function testSelectAllOnMainNamespace() {
		$row = new stdClass;
		$row->page_namespace = NS_MAIN;
		$row->page_title = 'Bar';

		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$whereConditions = [];
		$capturedSelects = [];
		$capturedTables = [];
		$selectBuilder = $this->createMockSelectQueryBuilder(
			[ $row ], $whereConditions, $capturedSelects, $capturedTables
		);
		$database->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$instance = new TitleLookup( $database );

		$this->assertArrayOfTitles(
			$instance->setNamespace( NS_MAIN )->selectAll()
		);

		$this->assertSame( [ 'page' ], $capturedTables );
	}

	public function testSelectByRangeOnCategoryNamespace() {
		$row = new stdClass;
		$row->cat_title = 'Foo';

		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$capturedWheres = [];
		$selectBuilder = $this->createMockSelectQueryBuilder( [ $row ], $capturedWheres );
		$database->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$instance = new TitleLookup( $database );

		$this->assertArrayOfTitles(
			$instance->setNamespace( NS_CATEGORY )->selectByIdRange( 1, 5 )
		);

		$this->assertSame(
			[ [ 'cat_id BETWEEN 1 AND 5' ] ],
			$capturedWheres
		);
	}

	public function testSelectByRangeOnMainNamespace() {
		$row = new stdClass;
		$row->page_namespace = NS_MAIN;
		$row->page_title = 'Bar';

		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$capturedWheres = [];
		$selectBuilder = $this->createMockSelectQueryBuilder( [ $row ], $capturedWheres );
		$database->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$instance = new TitleLookup( $database );

		$this->assertArrayOfTitles(
			$instance->setNamespace( NS_MAIN )->selectByIdRange( 6, 10 )
		);

		$this->assertSame(
			[ [
				'page_id BETWEEN 6 AND 10',
				'page_namespace' => NS_MAIN,
			] ],
			$capturedWheres
		);
	}

	public function testSelectAllOnMainNamespaceWithEmptyResult() {
		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$selectBuilder = $this->createMockSelectQueryBuilder( [] );
		$database->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$instance = new TitleLookup( $database );

		$this->assertArrayOfTitles(
			$instance->setNamespace( NS_MAIN )->selectAll()
		);
	}

	public function testSelectAllRedirectPages() {
		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$whereConditions = [];
		$capturedSelects = [];
		$capturedTables = [];
		$selectBuilder = $this->createMockSelectQueryBuilder(
			[], $whereConditions, $capturedSelects, $capturedTables
		);
		$database->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$instance = new TitleLookup( $database );

		$this->assertArrayOfTitles(
			$instance->getRedirectPages()
		);

		$this->assertSame( [ [ 'page', 'redirect' ] ], $capturedTables );
	}

	public function testMaxIdForMainNamespace() {
		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$whereConditions = [];
		$capturedSelects = [];
		$capturedTables = [];
		$selectBuilder = $this->createMockSelectQueryBuilder(
			[ (object)[ 'value' => 9999 ] ], $whereConditions, $capturedSelects, $capturedTables
		);
		$database->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$instance = new TitleLookup( $database );

		$this->assertEquals(
			9999,
			$instance->setNamespace( NS_MAIN )->getMaxId()
		);

		$this->assertSame( [ 'page' ], $capturedTables );
	}

	public function testgetMaxIdForCategoryNamespace() {
		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$whereConditions = [];
		$capturedSelects = [];
		$capturedTables = [];
		$selectBuilder = $this->createMockSelectQueryBuilder(
			[ (object)[ 'value' => 1111 ] ], $whereConditions, $capturedSelects, $capturedTables
		);
		$database->expects( $this->once() )
			->method( 'newSelectQueryBuilder' )
			->willReturn( $selectBuilder );

		$instance = new TitleLookup( $database );

		$this->assertEquals(
			1111,
			$instance->setNamespace( NS_CATEGORY )->getMaxId()
		);

		$this->assertSame( [ 'category' ], $capturedTables );
	}

	public function testSelectAllOnMissingNamespaceThrowsException() {
		$this->expectException( 'RuntimeException' );

		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TitleLookup( $database );
		$instance->selectAll();
	}

	public function testSelectByRangeOnMissingNamespaceThrowsException() {
		$this->expectException( 'RuntimeException' );

		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TitleLookup( $database );
		$instance->selectByIdRange( 1, 5 );
	}

	protected function assertArrayOfTitles( $arrayOfTitles ) {
		$this->assertIsArray( $arrayOfTitles );

		foreach ( $arrayOfTitles as $title ) {
			$this->assertInstanceOf( Title::class, $title );
		}
	}

}
