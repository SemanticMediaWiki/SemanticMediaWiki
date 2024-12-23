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
class HierarchyLookupTest extends \PHPUnit\Framework\TestCase {

	use PHPUnitCompat;

	private $store;
	private $cache;
	private $spyLogger;

	protected function setUp(): void {
		$this->spyLogger = TestEnvironment::newSpyLogger();

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

	public function testRegisterPropertyChangeListener() {
		$propertyChangeListener = $this->getMockBuilder( '\SMW\Listener\ChangeListener\ChangeListeners\PropertyChangeListener' )
			->disableOriginalConstructor()
			->getMock();
	
		$propertyChangeListener->expects( $this->exactly( 2 ) )
			->method( 'addListenerCallback' )
			->willReturnCallback( function ( $property, $callback ) {
				static $invocationCount = 0;
				$invocationCount++;
	
				match ( $invocationCount ) {
					1 => $this->assertEquals( new DIProperty( '_SUBP' ), $property, 'First invocation property should be _SUBP' ),
					2 => $this->assertEquals( new DIProperty( '_SUBC' ), $property, 'Second invocation property should be _SUBC' ),
					default => throw new LogicException( 'Unexpected invocation count' ),
				};
	
				// Optionally validate the callback if needed
				$this->assertNotNull( $callback, 'Callback should not be null' );
			});
	
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
		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getPropertySubjects' )
			->will( $this->returnValue( [] ) );

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new HierarchyLookup(
			$store,
			$cache
		);

		$instance->setLogger(
			$this->spyLogger
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

		$expected = [
			DIWikiPage::newFromText( 'Bar', SMW_NS_PROPERTY )
		];

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

		$instance->setLogger(
			$this->spyLogger
		);

		$this->assertEquals(
			$expected,
			$instance->findSubpropertyList( $property )
		);
	}

	public function testGetConsecutiveSubpropertyList() {
		$property = new DIProperty( 'Foo' );
	
		$expected = [
			new DIProperty( 'Bar' ),
			new DIProperty( 'Foobar' )
		];
	
		$a = DIWikiPage::newFromText( 'Bar', SMW_NS_PROPERTY );
		$b = DIWikiPage::newFromText( 'Foobar', SMW_NS_PROPERTY );
	
		// Track invocation count
		$this->store->expects( $this->exactly( 2 ) )
			->method( 'getPropertySubjects' )
			->willReturnCallback( function ( $diProperty, $wikiPage, $options ) use ( $a, $b, $property ) {
				static $invocationCount = 0;
				$invocationCount++;
	
				// Adjusting to use match correctly with return value
				return match ( $invocationCount ) {
					1 => [
						$this->assertEquals( new DIProperty( '_SUBP' ), $diProperty, 'First invocation property should be _SUBP' ),
						$this->assertEquals( $property->getDiWikiPage(), $wikiPage, 'First invocation wikiPage should match the given property' ),
						$a
					],
					2 => [
						$this->assertEquals( new DIProperty( '_SUBP' ), $diProperty, 'Second invocation property should be _SUBP' ),
						$this->assertEquals( $a, $wikiPage, 'Second invocation wikiPage should match the first property (Bar)' ),
						$b
					],
					default => throw new LogicException( 'Unexpected invocation count' ),
				};
			});
	
		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( false );
	
		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->stringContains( ':smw:hierarchy:2d440b72499319439ab5f466701f13fa' ),
				$this->anything()
			);
	
		$instance = new HierarchyLookup(
			$this->store,
			$this->cache
		);
	
		$instance->setLogger( $this->spyLogger );
		$instance->setSubpropertyDepth( 2 );
	
		$this->assertEquals(
			$expected,
			$instance->getConsecutiveHierarchyList( $property )
		);
	}

	public function testGetConsecutiveCachedSubpropertyList() {
		$property = new DIProperty( 'Foo' );

		$expected = [
			new DIProperty( 'Bar' ),
			new DIProperty( 'Foobar' )
		];

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( [ 'Foo' => [ 'Bar', 'Foobar' ] ] ) );

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
		$category = new DIWikiPage('Foo', NS_CATEGORY);
	
		$expected = [
			new DIWikiPage('Bar', NS_CATEGORY),
			new DIWikiPage('Foobar', NS_CATEGORY)
		];
	
		$a = DIWikiPage::newFromText('Bar', NS_CATEGORY);
		$b = DIWikiPage::newFromText('Foobar', NS_CATEGORY);
	
		// Track invocation count
		$this->store->expects($this->exactly(2))
			->method('getPropertySubjects')
			->willReturnCallback(function ($diProperty, $wikiPage, $options) use ($a, $b, $category) {
				static $invocationCount = 0;
				$invocationCount++;
	
				// Adjusting to use match correctly with return value
				return match ($invocationCount) {
					1 => [
						$this->assertEquals(new DIProperty('_SUBC'), $diProperty, 'First invocation property should be _SUBC'),
						$this->assertEquals($category, $wikiPage, 'First invocation wikiPage should match the given category'),
						$a
					],
					2 => [
						$this->assertEquals(new DIProperty('_SUBC'), $diProperty, 'Second invocation property should be _SUBC'),
						$this->assertEquals($a, $wikiPage, 'Second invocation wikiPage should match the first category (Bar)'),
						$b
					],
					default => throw new LogicException('Unexpected invocation count'),
				};
			});
	
		$this->cache->expects($this->once())
			->method('fetch')
			->willReturn(false);
	
		$this->cache->expects($this->once())
			->method('save')
			->with(
				$this->stringContains(':smw:hierarchy:78c9d3ed63c959981731afeef22cc8e9'),
				$this->anything()
			);
	
		$instance = new HierarchyLookup(
			$this->store,
			$this->cache
		);
	
		$instance->setLogger($this->spyLogger);
		$instance->setSubcategoryDepth(2);
	
		$this->assertEquals(
			$expected,
			$instance->getConsecutiveHierarchyList($category)
		);
	}	

	public function testGetConsecutiveSuperCategoryList() {
		$category = new DIWikiPage( 'Foo', NS_CATEGORY );
	
		$expected = [
			new DIWikiPage( 'Bar', NS_CATEGORY ),
			new DIWikiPage( 'Foobar', NS_CATEGORY )
		];
	
		$a = DIWikiPage::newFromText( 'Bar', NS_CATEGORY );
		$b = DIWikiPage::newFromText( 'Foobar', NS_CATEGORY );
	
		// Track invocation count
		$this->store->expects( $this->exactly( 2 ) )
			->method( 'getPropertySubjects' )
			->willReturnCallback( function ( $diProperty, $wikiPage, $options ) use ( $a, $b, $category ) {
				static $invocationCount = 0;
				$invocationCount++;
	
				return match ( $invocationCount ) {
					1 => [
						$this->assertEquals( new DIProperty( '_SUBC', true ), $diProperty, 'First invocation property should be _SUBC with true flag' ),
						$this->assertEquals( $category, $wikiPage, 'First invocation wikiPage should match the given category' ),
						$a
					],
					2 => [
						$this->assertEquals( new DIProperty( '_SUBC', true ), $diProperty, 'Second invocation property should be _SUBC with true flag' ),
						$this->assertEquals( $a, $wikiPage, 'Second invocation wikiPage should match the first category (Bar)' ),
						$b
					],
					default => throw new LogicException( 'Unexpected invocation count' ),
				};
			});
	
		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->willReturn( false );
	
		$this->cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->stringContains( ':smw:hierarchy:c61e6ee84187efaafbb31878af471432' ),
				$this->anything()
			);
	
		$instance = new HierarchyLookup(
			$this->store,
			$this->cache
		);
	
		$instance->setLogger( $this->spyLogger );
		$instance->setSubcategoryDepth( 2 );
	
		$this->assertEquals(
			$expected,
			$instance->getConsecutiveHierarchyList( $category, HierarchyLookup::TYPE_SUPER )
		);
	}

	public function testGetConsecutiveCachedSubcategoryList() {
		$category = new DIWikiPage( 'Foo', NS_CATEGORY );

		$expected = [
			new DIWikiPage( 'Bar', NS_CATEGORY ),
			new DIWikiPage( 'Foobar', NS_CATEGORY )
		];

		$this->cache->expects( $this->once() )
			->method( 'fetch' )
			->will( $this->returnValue( [ 'Foo' => [ 'Bar', 'Foobar' ] ] ) );

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

		$instance->getConsecutiveHierarchyList( new DIWikiPage( __METHOD__, NS_MAIN ) );
	}

	public function testFindSubcategoryList() {
		$category = DIWikiPage::newFromText( 'Foo', NS_CATEGORY );

		$expected = [
			DIWikiPage::newFromText( 'Bar', NS_CATEGORY )
		];

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

		$instance->setLogger(
			$this->spyLogger
		);

		$this->assertEquals(
			$expected,
			$instance->findSubcategoryList( $category )
		);
	}

	public function testFindNearbySuperCategories() {
		$category = DIWikiPage::newFromText( 'Foo', NS_CATEGORY );

		$expected = [
			DIWikiPage::newFromText( 'Bar', NS_CATEGORY )
		];

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getPropertySubjects' )
			->with(
				$this->equalTo( new DIProperty( '_SUBC', true ) ),
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

		$instance->setLogger(
			$this->spyLogger
		);

		$this->assertEquals(
			$expected,
			$instance->findNearbySuperCategories( $category )
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
