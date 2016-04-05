<?php

namespace SMW\Tests\Maintenance;

use SMW\Maintenance\DistinctEntityDataRebuilder;
use SMW\Options;
use SMW\Tests\TestEnvironment;
use Title;

/**
 * @covers \SMW\Maintenance\DistinctEntityDataRebuilder
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DistinctEntityDataRebuilderTest extends \PHPUnit_Framework_TestCase {

	protected $obLevel;
	private $connectionManager;
	private $testEnvironment;

	// The Store writes to the output buffer during drop/setupStore, to avoid
	// inappropriate buffer settings which can cause interference during unit
	// testing, we clean the output buffer
	protected function setUp() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->testEnvironment = new TestEnvironment();
		$this->testEnvironment->registerObject( 'Store', $store );

		$connection = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$connection->expects( $this->any() )
			->method( 'select' )
			->will( $this->returnValue( array() ) );

		$this->connectionManager = $this->getMockBuilder( '\SMW\ConnectionManager' )
			->disableOriginalConstructor()
			->getMock();

		$this->connectionManager->expects( $this->any() )
			->method( 'getConnection' )
			->will( $this->returnValue( $connection ) );

		$this->obLevel = ob_get_level();
		ob_start();

		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();
		$this->testEnvironment->tearDown();

		while ( ob_get_level() > $this->obLevel ) {
			ob_end_clean();
		}
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$titleCreator = $this->getMockBuilder( '\SMW\MediaWiki\TitleCreator' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Maintenance\DistinctEntityDataRebuilder',
			new DistinctEntityDataRebuilder( $store, $titleCreator )
		);
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testRebuildSelectedPagesWithQueryOption() {

		$subject = $this->getMockBuilder( '\SMW\DIWikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$subject->expects( $this->once() )
			->method( 'getTitle' )
			->will( $this->returnValue( Title::newFromText( __METHOD__ ) ) );

		$queryResult = $this->getMockBuilder( '\SMWQueryResult' )
			->disableOriginalConstructor()
			->getMock();

		$queryResult->expects( $this->once() )
			->method( 'getResults' )
			->will( $this->returnValue( array( $subject ) ) );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->at( 0 ) )
			->method( 'getQueryResult' )
			->will( $this->returnValue( 1 ) );

		$store->expects( $this->at( 1 ) )
			->method( 'getQueryResult' )
			->will( $this->returnValue( $queryResult ) );

		$store->setConnectionManager( $this->connectionManager );

		$titleCreator = $this->getMockBuilder( '\SMW\MediaWiki\TitleCreator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DistinctEntityDataRebuilder(
			$store,
			$titleCreator
		);

		$instance->setOptions( new Options( array(
			'query' => '[[Category:Foo]]'
		) ) );

		$this->assertTrue(
			$instance->doRebuild()
		);
	}

	public function testRebuildSelectedPagesWithCategoryNamespaceFilter() {

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
			->will( $this->returnValue( array( $row ) ) );

		$store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$titleCreator = $this->getMockBuilder( '\SMW\MediaWiki\TitleCreator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DistinctEntityDataRebuilder(
			$store,
			$titleCreator
		);

		$instance->setOptions( new Options( array(
			'categories' => true
		) ) );

		$this->assertTrue(
			$instance->doRebuild()
		);
	}

	public function testRebuildSelectedPagesWithPropertyNamespaceFilter() {

		$row = new \stdClass;
		$row->page_namespace = SMW_NS_PROPERTY;
		$row->page_title = 'Bar';

		$database = $this->getMockBuilder( '\SMW\MediaWiki\Database' )
			->disableOriginalConstructor()
			->getMock();

		$database->expects( $this->any() )
			->method( 'select' )
			->with( $this->anything(),
				$this->anything(),
				$this->equalTo( array( 'page_namespace' => SMW_NS_PROPERTY ) ),
				$this->anything(),
				$this->anything() )
			->will( $this->returnValue( array( $row ) ) );

		$store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$store->expects( $this->once() )
			->method( 'getConnection' )
			->will( $this->returnValue( $database ) );

		$titleCreator = $this->getMockBuilder( '\SMW\MediaWiki\TitleCreator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DistinctEntityDataRebuilder(
			$store,
			$titleCreator
		);

		$instance->setOptions( new Options( array(
			'p' => true
		) ) );

		$this->assertTrue(
			$instance->doRebuild()
		);
	}

	public function testRebuildSelectedPagesWithPageOption() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$titleCreator = $this->getMockBuilder( '\SMW\MediaWiki\TitleCreator' )
			->disableOriginalConstructor()
			->getMock();

		$titleCreator->expects( $this->at( 0 ) )
			->method( 'createFromText' )
			->with( $this->equalTo( 'Main page' ) )
			->will( $this->returnValue( Title::newFromText( 'Main page' ) ) );

		$titleCreator->expects( $this->at( 1 ) )
			->method( 'createFromText' )
			->with( $this->equalTo( 'Some other page' ) )
			->will( $this->returnValue( Title::newFromText( 'Some other page' ) ) );

		$titleCreator->expects( $this->at( 2 ) )
			->method( 'createFromText' )
			->with( $this->equalTo( 'Help:Main page' ) )
			->will( $this->returnValue( Title::newFromText( 'Main page', NS_HELP ) ) );

		$titleCreator->expects( $this->at( 3 ) )
			->method( 'createFromText' )
			->with( $this->equalTo( 'Main page' ) )
			->will( $this->returnValue( Title::newFromText( 'Main page' ) ) );

		$instance = new DistinctEntityDataRebuilder(
			$store,
			$titleCreator
		);

		$instance->setOptions( new Options( array(
			'page'  => 'Main page|Some other page|Help:Main page|Main page'
		) ) );

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
