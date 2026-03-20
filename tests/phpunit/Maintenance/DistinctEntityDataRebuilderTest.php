<?php

namespace SMW\Tests\Maintenance;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use SMW\Connection\ConnectionManager;
use SMW\DataItems\WikiPage;
use SMW\Maintenance\DistinctEntityDataRebuilder;
use SMW\MediaWiki\Connection\Database;
use SMW\MediaWiki\TitleFactory;
use SMW\Options;
use SMW\Query\QueryResult;
use SMW\SQLStore\SQLStore;
use SMW\Store;
use SMW\Tests\TestEnvironment;
use stdClass;

/**
 * @covers \SMW\Maintenance\DistinctEntityDataRebuilder
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class DistinctEntityDataRebuilderTest extends TestCase {

	protected $obLevel;
	private $connectionManager;
	private $testEnvironment;

	// The Store writes to the output buffer during drop/setupStore, to avoid
	// inappropriate buffer settings which can cause interference during unit
	// testing, we clean the output buffer
	protected function setUp(): void {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->setOption( 'smwgAutoRefreshSubject', true );

		$this->testEnvironment = new TestEnvironment();
		$spyLogger = $this->testEnvironment->newSpyLogger();

		$store->setLogger( $spyLogger );

		$this->testEnvironment->registerObject( 'Store', $store );

		$connection = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->willReturn( [] );

		$this->connectionManager = $this->getMockBuilder( ConnectionManager::class )
			->disableOriginalConstructor()
			->getMock();

		$this->connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $connection );

		$this->obLevel = ob_get_level();
		ob_start();

		parent::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		$this->testEnvironment->tearDown();

		while ( ob_get_level() > $this->obLevel ) {
			ob_end_clean();
		}
	}

	public function testCanConstruct() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			DistinctEntityDataRebuilder::class,
			new DistinctEntityDataRebuilder( $store, $titleFactory )
		);
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testRebuildSelectedPagesWithQueryOption() {
		$subject = $this->getMockBuilder( WikiPage::class )
			->disableOriginalConstructor()
			->getMock();

		$subject->expects( $this->once() )
			->method( 'getTitle' )
			->willReturn( MediaWikiServices::getInstance()->getTitleFactory()->newFromText( __METHOD__ ) );

		$queryResult = $this->getMockBuilder( QueryResult::class )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getResults' )
			->willReturn( [ $subject ] );

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$callCount = 0;
		$store->expects( $this->exactly( 2 ) )
			->method( 'getQueryResult' )
			->willReturnCallback( static function () use ( &$callCount, $queryResult ) {
				$callCount++;
				return $callCount === 1 ? 1 : $queryResult;
			} );

		$store->setConnectionManager( $this->connectionManager );

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DistinctEntityDataRebuilder(
			$store,
			$titleFactory
		);

		$instance->setOptions( new Options( [
			'query' => '[[Category:Foo]]'
		] ) );

		$this->assertTrue(
			$instance->doRebuild()
		);
	}

	public function testRebuildSelectedPagesWithCategoryNamespaceFilter() {
		$row = new stdClass;
		$row->cat_title = 'Foo';

		$database = $this->getMockBuilder( Database::class )
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

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $database );

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DistinctEntityDataRebuilder(
			$store,
			$titleFactory
		);

		$instance->setOptions( new Options( [
			'categories' => true
		] ) );

		$this->assertTrue(
			$instance->doRebuild()
		);
	}

	public function testRebuildSelectedPagesWithPropertyNamespaceFilter() {
		$row = new stdClass;
		$row->page_namespace = SMW_NS_PROPERTY;
		$row->page_title = 'Bar';

		$database = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->any() )
			->method( 'select' )
			->with( $this->anything(),
				$this->anything(),
				[ 'page_namespace' => SMW_NS_PROPERTY ],
				$this->anything(),
				$this->anything() )
			->willReturn( [ $row ] );

		$store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->willReturn( $database );

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DistinctEntityDataRebuilder(
			$store,
			$titleFactory
		);

		$instance->setOptions( new Options( [
			'p' => true
		] ) );

		$this->assertTrue(
			$instance->doRebuild()
		);
	}

	public function testRebuildSelectedPagesWithPageOption() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$titleFactory = $this->getMockBuilder( TitleFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$mwTitleFactory = MediaWikiServices::getInstance()->getTitleFactory();

		$titleFactory->expects( $this->exactly( 4 ) )
			->method( 'newFromText' )
			->willReturnCallback( static function ( $title ) use ( $mwTitleFactory ) {
				if ( $title === 'Help:Main page' ) {
					return $mwTitleFactory->newFromText( 'Main page', NS_HELP );
				}
				return $mwTitleFactory->newFromText( $title );
			} );

		$instance = new DistinctEntityDataRebuilder(
			$store,
			$titleFactory
		);

		$instance->setOptions( new Options( [
			'page'  => 'Main page|Some other page|Help:Main page|Main page'
		] ) );

		$this->assertTrue(
			$instance->doRebuild()
		);

		$this->assertEquals(
			3,
			$instance->getRebuildCount()
		);
	}

	/**
	 * @see Store::refreshData
	 */
	public function refreshDataOnMockCallback( &$index ) {
		$index++;
	}

}
