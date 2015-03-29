<?php

namespace SMW\Tests\Maintenance;

use SMW\Maintenance\DataRebuilder;

use Title;

/**
 * @covers \SMW\Maintenance\DataRebuilder
 *
 * @group semantic-mediawiki
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 1.9.2
 *
 * @author mwjames
 */
class DataRebuilderTest extends \PHPUnit_Framework_TestCase {

	protected $obLevel;

	// The Store writes to the output buffer during drop/setupStore, to avoid
	// inappropriate buffer settings which can cause interference during unit
	// testing, we clean the output buffer
	protected function setUp() {
		$this->obLevel = ob_get_level();
		ob_start();

		parent::setUp();
	}

	protected function tearDown() {
		parent::tearDown();

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
			'\SMW\Maintenance\DataRebuilder',
			new DataRebuilder( $store, $titleCreator )
		);
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testRebuildAllWithoutOptions() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'refreshData' ) )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'refreshData' )
			->will( $this->returnCallback( array( $this, 'refreshDataOnMockCallback' ) ) );

		$titleCreator = $this->getMockBuilder( '\SMW\MediaWiki\TitleCreator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataRebuilder( $store, $titleCreator );

		// Needs an end otherwise phpunit is caught up in an infinite loop
		$instance->setParameters( array(
			'e' => 1
		) );

		$this->assertTrue( $instance->rebuild() );
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testRebuildAllWithFullDelete() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array(
				'refreshData',
				'drop' ) )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'refreshData' )
			->will( $this->returnCallback( array( $this, 'refreshDataOnMockCallback' ) ) );

		$store->expects( $this->once() )
			->method( 'drop' );

		$titleCreator = $this->getMockBuilder( '\SMW\MediaWiki\TitleCreator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataRebuilder( $store, $titleCreator );

		$instance->setParameters( array(
			'e' => 1,
			'f' => true,
			'verbose' => false
		) );

		$this->assertTrue( $instance->rebuild() );
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testRebuildAllWithStopRangeOption() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( array( 'refreshData' ) )
			->getMockForAbstractClass();

		$store->expects( $this->exactly( 6 ) )
			->method( 'refreshData' )
			->will( $this->returnCallback( array( $this, 'refreshDataOnMockCallback' ) ) );

		$titleCreator = $this->getMockBuilder( '\SMW\MediaWiki\TitleCreator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataRebuilder( $store, $titleCreator );

		$instance->setParameters( array(
			's' => 2,
			'n' => 5,
			'verbose' => false
		) );

		$this->assertTrue( $instance->rebuild() );
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

		$titleCreator = $this->getMockBuilder( '\SMW\MediaWiki\TitleCreator' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new DataRebuilder( $store, $titleCreator );

		$instance->setParameters( array(
			'query' => '[[Category:Foo]]'
		) );

		$this->assertTrue( $instance->rebuild() );
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

		$instance = new DataRebuilder( $store, $titleCreator );

		$instance->setParameters( array(
			'c' => true
		) );

		$this->assertTrue( $instance->rebuild() );
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

		$instance = new DataRebuilder( $store, $titleCreator );

		$instance->setParameters( array(
			'p' => true
		) );

		$this->assertTrue( $instance->rebuild() );
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

		$instance = new DataRebuilder( $store, $titleCreator );

		$instance->setParameters( array(
			'page'  => 'Main page|Some other page|Help:Main page|Main page'
		) );

		$this->assertTrue( $instance->rebuild() );

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
