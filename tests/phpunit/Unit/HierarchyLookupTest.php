<?php

namespace SMW\Tests;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\HierarchyLookup;

/**
 * @covers \SMW\HierarchyLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class HierarchyLookupTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $cache;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			HierarchyLookup::class,
			new HierarchyLookup( $this->store, $this->cache )
		);
	}

	public function testAddChangePropListener() {

		$changePropListener = $this->getMockBuilder( '\SMW\ChangePropListener' )
			->disableOriginalConstructor()
			->getMock();

		$changePropListener->expects( $this->at( 0 ) )
			->method( 'addListenerCallback' )
			->with(
				$this->equalTo( '_SUBP' ),
				$this->anything() );

		$changePropListener->expects( $this->at( 1 ) )
			->method( 'addListenerCallback' )
			->with(
				$this->equalTo( '_SUBC' ),
				$this->anything() );

		$instance = new HierarchyLookup(
			$this->store,
			$this->cache
		);

		$instance->addListenersTo( $changePropListener );
	}

	public function testVerifySubpropertyForOnNonCachedLookup() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( array() ) );

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new HierarchyLookup(
			$store,
			$cache
		);

		$property = new DIProperty( 'Foo' );
		$instance->hasSubproperty( $property );

		$this->assertInternalType(
			'boolean',
			$instance->hasSubproperty( $property )
		);
	}

	public function testFindSubpropertyList() {

		$property = new DIProperty( 'Foo' );

		$expected = array(
			DIWikiPage::newFromText( 'Bar', SMW_NS_PROPERTY )
		);

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getPropertySubjects' )
			->with(
				$this->equalTo( new DIProperty( '_SUBP' ) ),
				$this->equalTo( $property->getDiWikiPage() ),
				$this->anything() )
			->will( $this->returnValue( $expected ) );

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new HierarchyLookup(
			$store,
			$cache
		);

		$this->assertEquals(
			$expected,
			$instance->findSubpropertyList( $property )
		);
	}

	public function testGetConsecutiveSubpropertyList() {

		$property = new DIProperty( 'Foo' );

		$expected = array(
			new DIProperty( 'Bar' ),
			new DIProperty( 'Foobar' )
		);

		$a = DIWikiPage::newFromText( 'Bar', SMW_NS_PROPERTY );

		$this->store->expects( $this->at( 0 ) )
			->method( 'getPropertySubjects' )
			->with(
				$this->equalTo( new DIProperty( '_SUBP' ) ),
				$this->equalTo( $property->getDiWikiPage() ),
				$this->anything() )
			->will( $this->returnValue( [ $a ] ) );

		$b = DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY );

		$this->store->expects( $this->at( 1 ) )
			->method( 'getPropertySubjects' )
			->with(
				$this->equalTo( new DIProperty( '_SUBP' ) ),
				$this->equalTo( $a ),
				$this->anything() )
			->will( $this->returnValue( [ $b ] ) );

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->stringContains( ':smw:hlkp:eebbf01df970d569b285cb5b417c7ec3' ),
				$this->anything() );

		$instance = new HierarchyLookup(
			$this->store,
			$this->cache
		);

		$instance->setSubpropertyDepth( 2 );

		$this->assertEquals(
			$expected,
			$instance->getConsecutiveHierarchyList( $property )
		);
	}

	public function testGetConsecutiveCachedSubpropertyList() {

		$property = new DIProperty( 'Foo' );

		$expected = array(
			new DIProperty( 'Bar' ),
			new DIProperty( 'Foobar' )
		);

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( [ 'Foo' => [ 'Bar', 'Foobar' ] ] ) );

		$this->cache->expects( $this->never() )
			->method( 'save' );

		$instance = new HierarchyLookup(
			$this->store,
			$this->cache
		);

		$instance->setSubpropertyDepth( 2 );

		$this->assertEquals(
			$expected,
			$instance->getConsecutiveHierarchyList( $property )
		);
	}

	public function testGetConsecutiveSubcategoryList() {

		$category = new DIWikiPage( 'Foo', NS_CATEGORY );

		$expected = array(
			new DIWikiPage( 'Bar', NS_CATEGORY ),
			new DIWikiPage( 'Foobar', NS_CATEGORY )
		);

		$a = DIWikiPage::newFromText( 'Bar', NS_CATEGORY );

		$this->store->expects( $this->at( 0 ) )
			->method( 'getPropertySubjects' )
			->with(
				$this->equalTo( new DIProperty( '_SUBC' ) ),
				$this->equalTo( $category ),
				$this->anything() )
			->will( $this->returnValue( [ $a ] ) );

		$b = DIWikiPage::newFromText( 'Foobar', NS_CATEGORY );

		$this->store->expects( $this->at( 1 ) )
			->method( 'getPropertySubjects' )
			->with(
				$this->equalTo( new DIProperty( '_SUBC' ) ),
				$this->equalTo( $a ),
				$this->anything() )
			->will( $this->returnValue( [ $b ] ) );

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( false ) );

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->stringContains( ':smw:hlkp:28d64caa88a077bb0746e3928f06e353' ),
				$this->anything() );

		$instance = new HierarchyLookup(
			$this->store,
			$this->cache
		);

		$instance->setSubcategoryDepth( 2 );

		$this->assertEquals(
			$expected,
			$instance->getConsecutiveHierarchyList( $category )
		);
	}

	public function testGetConsecutiveCachedSubcategoryList() {

		$category = new DIWikiPage( 'Foo', NS_CATEGORY );

		$expected = array(
			new DIWikiPage( 'Bar', NS_CATEGORY ),
			new DIWikiPage( 'Foobar', NS_CATEGORY )
		);

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( [ 'Foo' => [ 'Bar', 'Foobar' ] ] ) );

		$this->cache->expects( $this->never() )
			->method( 'save' );

		$instance = new HierarchyLookup(
			$this->store,
			$this->cache
		);

		$instance->setSubcategoryDepth( 2 );

		$this->assertEquals(
			$expected,
			$instance->getConsecutiveHierarchyList( $category )
		);
	}

	public function testGetConsecutiveHierarchyListWithInvaidTypeThrowsException() {

		$instance = new HierarchyLookup(
			$this->store,
			$this->cache
		);

		$this->setExpectedException( 'InvalidArgumentException' );

		$instance->getConsecutiveHierarchyList( new DIWikiPage( __METHOD__, NS_MAIN ) );
	}

	public function testFindSubcategoryList() {

		$category = DIWikiPage::newFromText( 'Foo', NS_CATEGORY );

		$expected = array(
			DIWikiPage::newFromText( 'Bar', NS_CATEGORY )
		);

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getPropertySubjects' )
			->with(
				$this->equalTo( new DIProperty( '_SUBC' ) ),
				$this->equalTo( $category ),
				$this->anything() )
			->will( $this->returnValue( $expected ) );

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new HierarchyLookup(
			$store,
			$cache
		);

		$this->assertEquals(
			$expected,
			$instance->findSubcategoryList( $category )
		);
	}

	public function testDisabledSubpropertyLookup() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->never() )
			->method( 'contains' )
			->will( $this->returnValue( false ) );

		$instance = new HierarchyLookup( $store, $cache );
		$instance->setSubpropertyDepth( 0 );

		$this->assertFalse(
			$instance->hasSubproperty( new DIProperty( 'Foo' ) )
		);
	}

	public function testDisabledSubcategoryLookup() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->never() )
			->method( 'contains' )
			->will( $this->returnValue( false ) );

		$instance = new HierarchyLookup( $store, $cache );
		$instance->setSubcategoryDepth( 0 );

		$this->assertFalse(
			$instance->hasSubcategory( DIWikiPage::newFromText( 'Foo', NS_CATEGORY ) )
		);
	}

}
