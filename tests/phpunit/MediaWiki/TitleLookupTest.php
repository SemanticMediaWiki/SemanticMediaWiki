<?php

namespace SMW\Tests\MediaWiki;

use RuntimeException;
use Title;
use SMW\MediaWiki\TitleLookup;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\MediaWiki\TitleLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class TitleLookupTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	public function testCanConstruct() {
		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\MediaWiki\TitleLookup',
			new TitleLookup( $database )
		);
	}

	public function testSelectAllOnCategoryNamespace() {
		$row = new \stdClass;
		$row->cat_title = 'Foo';

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->any() )
			->method( 'select' )
			->with( $this->stringContains( 'category' ),
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->anything() )
			->willReturn( [ $row ] );

		$instance = new TitleLookup( $database );

		$this->assertArrayOfTitles(
			$instance->setNamespace( NS_CATEGORY )->selectAll()
		);
	}

	public function testSelectAllOnMainNamespace() {
		$row = new \stdClass;
		$row->page_namespace = NS_MAIN;
		$row->page_title = 'Bar';

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->any() )
			->method( 'select' )
			->with( $this->anything(),
				$this->anything(),
				[ 'page_namespace' => NS_MAIN ],
				$this->anything(),
				$this->anything() )
			->willReturn( [ $row ] );

		$instance = new TitleLookup( $database );

		$this->assertArrayOfTitles(
			$instance->setNamespace( NS_MAIN )->selectAll()
		);
	}

	public function testSelectByRangeOnCategoryNamespace() {
		$row = new \stdClass;
		$row->cat_title = 'Foo';

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->any() )
			->method( 'select' )
			->with( $this->stringContains( 'category' ),
				$this->anything(),
				[ "cat_id BETWEEN 1 AND 5" ],
				$this->anything(),
				$this->anything() )
			->willReturn( [ $row ] );

		$instance = new TitleLookup( $database );

		$this->assertArrayOfTitles(
			$instance->setNamespace( NS_CATEGORY )->selectByIdRange( 1, 5 )
		);
	}

	public function testSelectByRangeOnMainNamespace() {
		$row = new \stdClass;
		$row->page_namespace = NS_MAIN;
		$row->page_title = 'Bar';

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->any() )
			->method( 'select' )
			->with( $this->anything(),
				$this->anything(),
				$this->equalTo( [ "page_id BETWEEN 6 AND 10", 'page_namespace' => NS_MAIN ] ),
				$this->anything(),
				$this->anything() )
			->willReturn( [ $row ] );

		$instance = new TitleLookup( $database );

		$this->assertArrayOfTitles(
			$instance->setNamespace( NS_MAIN )->selectByIdRange( 6, 10 )
		);
	}

	public function testSelectAllOnMainNamespaceWithEmptyResult() {
		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->any() )
			->method( 'select' )
			->with( $this->anything(),
				$this->anything(),
				[ 'page_namespace' => NS_MAIN ],
				$this->anything(),
				$this->anything() )
			->willReturn( false );

		$instance = new TitleLookup( $database );

		$this->assertArrayOfTitles(
			$instance->setNamespace( NS_MAIN )->selectAll()
		);
	}

	public function testSelectAllRedirectPages() {
		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->any() )
			->method( 'select' )
			->with(
				$this->equalTo( [ 'page', 'redirect' ] ),
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->anything() )
			->willReturn( false );

		$instance = new TitleLookup( $database );

		$this->assertArrayOfTitles(
			$instance->getRedirectPages()
		);
	}

	public function testMaxIdForMainNamespace() {
		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->once() )
			->method( 'selectField' )
			->with( 'page',
				$this->anything(),
				$this->anything(),
				$this->anything() )
			->willReturn( 9999 );

		$instance = new TitleLookup( $database );

		$this->assertEquals(
			9999,
			$instance->setNamespace( NS_MAIN )->getMaxId()
		);
	}

	public function testgetMaxIdForCategoryNamespace() {
		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->once() )
			->method( 'selectField' )
			->with( 'category',
				$this->anything(),
				$this->anything(),
				$this->anything() )
			->willReturn( 1111 );

		$instance = new TitleLookup( $database );

		$this->assertEquals(
			1111,
			$instance->setNamespace( NS_CATEGORY )->getMaxId()
		);
	}

	public function testSelectAllOnMissingNamespaceThrowsException() {
		$this->expectException( 'RuntimeException' );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TitleLookup( $database );
		$instance->selectAll();
	}

	public function testSelectByRangeOnMissingNamespaceThrowsException() {
		$this->expectException( 'RuntimeException' );

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new TitleLookup( $database );
		$instance->selectByIdRange( 1, 5 );
	}

	protected function assertArrayOfTitles( $arrayOfTitles ) {
		$this->assertIsArray( $arrayOfTitles );

		foreach ( $arrayOfTitles as $title ) {
			$this->assertInstanceOf( 'Title', $title );
		}
	}

}
