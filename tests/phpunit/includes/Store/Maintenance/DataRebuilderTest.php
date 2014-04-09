<?php

namespace SMW\Tests\Store\Maintenance;

use SMW\Store\Maintenance\DataRebuilder;

use Title;

/**
 * @covers \SMW\Store\Maintenance\DataRebuilder
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-unit
 * @group mediawiki-databaseless
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

		$store = $this->getMockForAbstractClass( '\SMW\Store' );

		$messagereporter = $this->getMockBuilder( '\SMW\Messagereporter' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Store\Maintenance\DataRebuilder',
			new DataRebuilder( $store, $messagereporter )
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

		$messagereporter = $this->getMockBuilder( '\SMW\Messagereporter' )
			->disableOriginalConstructor()
			->setMethods( array( 'reportMessage' ) )
			->getMock();

		$instance = new DataRebuilder(
			$store,
			$messagereporter
		);

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
				'drop',
				'setupStore' ) )
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'refreshData' )
			->will( $this->returnCallback( array( $this, 'refreshDataOnMockCallback' ) ) );

		$store->expects( $this->once() )
			->method( 'drop' );

		$store::staticExpects( $this->once() )
			->method( 'setupStore' );

		$messagereporter = $this->getMockBuilder( '\SMW\Messagereporter' )
			->disableOriginalConstructor()
			->setMethods( array( 'reportMessage' ) )
			->getMock();

		$instance = new DataRebuilder(
			$store,
			$messagereporter
		);

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

		$messagereporter = $this->getMockBuilder( '\SMW\Messagereporter' )
			->disableOriginalConstructor()
			->setMethods( array( 'reportMessage' ) )
			->getMock();

		$instance = new DataRebuilder(
			$store,
			$messagereporter
		);

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

		$messagereporter = $this->getMockBuilder( '\SMW\Messagereporter' )
			->disableOriginalConstructor()
			->setMethods( array( 'reportMessage' ) )
			->getMock();

		$instance = new DataRebuilder(
			$store,
			$messagereporter
		);

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
			->setMethods( array( 'getDatabase' ) )
			->getMock();

		$store->expects( $this->once() )
			->method( 'getDatabase' )
			->will( $this->returnValue( $database ) );

		$messagereporter = $this->getMockBuilder( '\SMW\Messagereporter' )
			->disableOriginalConstructor()
			->setMethods( array( 'reportMessage' ) )
			->getMock();

		$instance = new DataRebuilder(
			$store,
			$messagereporter
		);

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
			->setMethods( array( 'getDatabase' ) )
			->getMock();

		$store->expects( $this->once() )
			->method( 'getDatabase' )
			->will( $this->returnValue( $database ) );

		$messagereporter = $this->getMockBuilder( '\SMW\Messagereporter' )
			->disableOriginalConstructor()
			->setMethods( array( 'reportMessage' ) )
			->getMock();

		$instance = new DataRebuilder(
			$store,
			$messagereporter
		);

		$instance->setParameters( array(
			'p' => true
		) );

		$this->assertTrue( $instance->rebuild() );
	}

	public function testRebuildSelectedPagesWithPageOption() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$messagereporter = $this->getMockBuilder( '\SMW\Messagereporter' )
			->disableOriginalConstructor()
			->setMethods( array( 'reportMessage' ) )
			->getMock();

		$instance = $this->getMockBuilder( '\SMW\Store\Maintenance\DataRebuilder' )
			->setConstructorArgs( array( $store, $messagereporter ) )
			->setMethods( array( 'makeTitleOf' ) )
			->getMock();

		$instance->expects( $this->at( 0 ) )
			->method( 'makeTitleOf' )
			->with( $this->equalTo( 'Main page' ) )
			->will( $this->returnValue( Title::newFromText( 'Main page' ) ) );

		$instance->expects( $this->at( 1 ) )
			->method( 'makeTitleOf' )
			->with( $this->equalTo( 'Some other page' ) )
			->will( $this->returnValue( Title::newFromText( 'Some other page' ) ) );

		$instance->expects( $this->at( 2 ) )
			->method( 'makeTitleOf' )
			->with( $this->equalTo( 'Main page' ) )
			->will( $this->returnValue( Title::newFromText( 'Main page' ) ) );

		$instance->setParameters( array(
			'page'  => 'Main page|Some other page|Main page'
		) );

		$this->assertTrue( $instance->rebuild() );

		$this->assertEquals( 2, $instance->getRebuildCount() );
	}

	/**
	 * @see Store::refreshData
	 */
	public function refreshDataOnMockCallback( &$index, $count, $namespaces, $usejobs ) {
		$index++;
	}

}