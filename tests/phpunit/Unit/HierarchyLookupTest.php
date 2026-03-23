<?php

namespace SMW\Tests\Unit;

use Onoi\Cache\Cache;
use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\HierarchyLookup;
use SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener;
use SMW\Store;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\HierarchyLookup
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.3
 *
 * @author mwjames
 */
class HierarchyLookupTest extends TestCase {

	private $store;
	private $cache;
	private $spyLogger;

	protected function setUp(): void {
		$this->spyLogger = TestEnvironment::newSpyLogger();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {
		$this->assertInstanceOf(
			HierarchyLookup::class,
			new HierarchyLookup( $this->store, $this->cache )
		);
	}

	public function testRegisterPropertyChangeListener() {
		$propertyChangeListener = $this->getMockBuilder( PropertyChangeListener::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyChangeListener->expects( $this->exactly( 2 ) )
			->method( 'addListenerCallback' )
			->willReturnCallback( function ( $property, $callback ) {
				static $calls = [];
				$calls[] = $property;
				if ( count( $calls ) === 1 ) {
					$this->assertEquals( new Property( '_SUBP' ), $property );
				} elseif ( count( $calls ) === 2 ) {
					$this->assertEquals( new Property( '_SUBC' ), $property );
				}
			} );

		$instance = new HierarchyLookup(
			$this->store,
			$this->cache
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->registerPropertyChangeListener( $propertyChangeListener );
	}

	public function testVerifySubpropertyForOnNonCachedLookup() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getPropertySubjects' )
			->willReturn( [] );

		$cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new HierarchyLookup(
			$store,
			$cache
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$property = new Property( 'Foo' );
		$instance->hasSubproperty( $property );

		$this->assertIsBool(

			$instance->hasSubproperty( $property )
		);
	}

	public function testFindSubpropertyList() {
		$property = new Property( 'Foo' );

		$expected = [
			WikiPage::newFromText( 'Bar', SMW_NS_PROPERTY )
		];

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getPropertySubjects' )
			->with(
				new Property( '_SUBP' ),
				$property->getDiWikiPage(),
				$this->anything() )
			->willReturn( $expected );

		$cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new HierarchyLookup(
			$store,
			$cache
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$this->assertEquals(
			$expected,
			$instance->findSubpropertyList( $property )
		);
	}

	public function testGetConsecutiveSubpropertyList() {
		$property = new Property( 'Foo' );

		$expected = [
			new Property( 'Bar' ),
			new Property( 'Foobar' )
		];

		$a = WikiPage::newFromText( 'Bar', SMW_NS_PROPERTY );
		$b = WikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY );

		$this->store->expects( $this->exactly( 2 ) )
			->method( 'getPropertySubjects' )
			->willReturnCallback( function ( $prop, $subject, $requestOptions ) use ( $property, $a, $b ) {
				static $calls = [];
				$calls[] = $subject;
				if ( count( $calls ) === 1 ) {
					$this->assertEquals( new Property( '_SUBP' ), $prop );
					$this->assertEquals( $property->getDiWikiPage(), $subject );
					return [ $a ];
				}
				$this->assertEquals( new Property( '_SUBP' ), $prop );
				$this->assertEquals( $a, $subject );
				return [ $b ];
			} );

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( false );

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->stringContains( ':smw:hierarchy:2d440b72499319439ab5f466701f13fa' ),
				$this->anything() );

		$instance = new HierarchyLookup(
			$this->store,
			$this->cache
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->setSubpropertyDepth( 2 );

		$this->assertEquals(
			$expected,
			$instance->getConsecutiveHierarchyList( $property )
		);
	}

	public function testGetConsecutiveCachedSubpropertyList() {
		$property = new Property( 'Foo' );

		$expected = [
			new Property( 'Bar' ),
			new Property( 'Foobar' )
		];

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( [ 'Foo' => [ 'Bar', 'Foobar' ] ] );

		$this->cache->expects( $this->never() )
			->method( 'save' );

		$instance = new HierarchyLookup(
			$this->store,
			$this->cache
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->setSubpropertyDepth( 2 );

		$this->assertEquals(
			$expected,
			$instance->getConsecutiveHierarchyList( $property )
		);
	}

	public function testGetConsecutiveSubcategoryList() {
		$category = new WikiPage( 'Foo', NS_CATEGORY );

		$expected = [
			new WikiPage( 'Bar', NS_CATEGORY ),
			new WikiPage( 'Foobar', NS_CATEGORY )
		];

		$a = WikiPage::newFromText( 'Bar', NS_CATEGORY );
		$b = WikiPage::newFromText( 'Foobar', NS_CATEGORY );

		$this->store->expects( $this->exactly( 2 ) )
			->method( 'getPropertySubjects' )
			->willReturnCallback( function ( $prop, $subject, $requestOptions ) use ( $category, $a, $b ) {
				static $calls = [];
				$calls[] = $subject;
				if ( count( $calls ) === 1 ) {
					$this->assertEquals( new Property( '_SUBC' ), $prop );
					$this->assertEquals( $category, $subject );
					return [ $a ];
				}
				$this->assertEquals( new Property( '_SUBC' ), $prop );
				$this->assertEquals( $a, $subject );
				return [ $b ];
			} );

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( false );

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->stringContains( ':smw:hierarchy:78c9d3ed63c959981731afeef22cc8e9' ),
				$this->anything() );

		$instance = new HierarchyLookup(
			$this->store,
			$this->cache
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->setSubcategoryDepth( 2 );

		$this->assertEquals(
			$expected,
			$instance->getConsecutiveHierarchyList( $category )
		);
	}

	public function testGetConsecutiveSuperCategoryList() {
		$category = new WikiPage( 'Foo', NS_CATEGORY );

		$expected = [
			new WikiPage( 'Bar', NS_CATEGORY ),
			new WikiPage( 'Foobar', NS_CATEGORY )
		];

		$a = WikiPage::newFromText( 'Bar', NS_CATEGORY );
		$b = WikiPage::newFromText( 'Foobar', NS_CATEGORY );

		$this->store->expects( $this->exactly( 2 ) )
			->method( 'getPropertySubjects' )
			->willReturnCallback( function ( $prop, $subject, $requestOptions ) use ( $category, $a, $b ) {
				static $calls = [];
				$calls[] = $subject;
				if ( count( $calls ) === 1 ) {
					$this->assertEquals( new Property( '_SUBC', true ), $prop );
					$this->assertEquals( $category, $subject );
					return [ $a ];
				}
				$this->assertEquals( new Property( '_SUBC', true ), $prop );
				$this->assertEquals( $a, $subject );
				return [ $b ];
			} );

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( false );

		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->stringContains( ':smw:hierarchy:c61e6ee84187efaafbb31878af471432' ),
				$this->anything() );

		$instance = new HierarchyLookup(
			$this->store,
			$this->cache
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$instance->setSubcategoryDepth( 2 );

		$this->assertEquals(
			$expected,
			$instance->getConsecutiveHierarchyList( $category, HierarchyLookup::TYPE_SUPER )
		);
	}

	public function testGetConsecutiveCachedSubcategoryList() {
		$category = new WikiPage( 'Foo', NS_CATEGORY );

		$expected = [
			new WikiPage( 'Bar', NS_CATEGORY ),
			new WikiPage( 'Foobar', NS_CATEGORY )
		];

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( [ 'Foo' => [ 'Bar', 'Foobar' ] ] );

		$this->cache->expects( $this->never() )
			->method( 'save' );

		$instance = new HierarchyLookup(
			$this->store,
			$this->cache
		);

		$instance->setLogger(
			$this->spyLogger
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

		$this->expectException( 'InvalidArgumentException' );

		$instance->getConsecutiveHierarchyList( new WikiPage( __METHOD__, NS_MAIN ) );
	}

	public function testFindSubcategoryList() {
		$category = WikiPage::newFromText( 'Foo', NS_CATEGORY );

		$expected = [
			WikiPage::newFromText( 'Bar', NS_CATEGORY )
		];

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getPropertySubjects' )
			->with(
				new Property( '_SUBC' ),
				$category,
				$this->anything() )
			->willReturn( $expected );

		$cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new HierarchyLookup(
			$store,
			$cache
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$this->assertEquals(
			$expected,
			$instance->findSubcategoryList( $category )
		);
	}

	public function testFindNearbySuperCategories() {
		$category = WikiPage::newFromText( 'Foo', NS_CATEGORY );

		$expected = [
			WikiPage::newFromText( 'Bar', NS_CATEGORY )
		];

		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getPropertySubjects' )
			->with(
				new Property( '_SUBC', true ),
				$category,
				$this->anything() )
			->willReturn( $expected );

		$cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();

		$instance = new HierarchyLookup(
			$store,
			$cache
		);

		$instance->setLogger(
			$this->spyLogger
		);

		$this->assertEquals(
			$expected,
			$instance->findNearbySuperCategories( $category )
		);
	}

	public function testDisabledSubpropertyLookup() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->never() )
			->method( 'contains' )
			->willReturn( false );

		$instance = new HierarchyLookup( $store, $cache );
		$instance->setSubpropertyDepth( 0 );

		$this->assertFalse(
			$instance->hasSubproperty( new Property( 'Foo' ) )
		);
	}

	public function testDisabledSubcategoryLookup() {
		$store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$cache = $this->getMockBuilder( Cache::class )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->never() )
			->method( 'contains' )
			->willReturn( false );

		$instance = new HierarchyLookup( $store, $cache );
		$instance->setSubcategoryDepth( 0 );

		$this->assertFalse(
			$instance->hasSubcategory( WikiPage::newFromText( 'Foo', NS_CATEGORY ) )
		);
	}

}
