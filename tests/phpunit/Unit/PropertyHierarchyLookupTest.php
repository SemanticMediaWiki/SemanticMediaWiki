<?php

namespace SMW\Tests;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\PropertyHierarchyLookup;

/**
 * @covers \SMW\PropertyHierarchyLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class PropertyHierarchyLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\PropertyHierarchyLookup',
			new PropertyHierarchyLookup( $store, $cache )
		);
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

		$cache->expects( $this->once() )
			->method( 'contains' )
			->will( $this->returnValue( false ) );

		$cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->equalTo( '_SUBP#Foo#5230d9b41a617ba30fa77223b431507c' ),
				$this->equalTo( array() ) );

		$instance = new PropertyHierarchyLookup( $store, $cache );

		$this->assertInternalType(
			'boolean',
			$instance->hasSubpropertyFor( new DIProperty( 'Foo' ) )
		);
	}

	public function testFindSubpropertyList() {

		$property = new DIProperty( 'Foo' );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getPropertySubjects' )
			->with(
				$this->equalTo( new DIProperty( '_SUBP' ) ),
				$this->equalTo( $property->getDiWikiPage() ),
				$this->anything() )
			->will( $this->returnValue( array( DIWikiPage::newFromText( 'Bar', SMW_NS_PROPERTY ) ) ) );

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->once() )
			->method( 'contains' )
			->will( $this->returnValue( false ) );

		$cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->equalTo( '_SUBP#Foo#b3ed5707ed115aa0ef22f567c08c35df' ),
				$this->anything() );

		$instance = new PropertyHierarchyLookup( $store, $cache );

		$expected = array(
			DIWikiPage::newFromText( 'Bar', SMW_NS_PROPERTY )
		);

		$this->assertEquals(
			$expected,
			$instance->findSubpropertListFor( $property )
		);
	}

	public function testFindSubcategoryList() {

		$category = DIWikiPage::newFromText( 'Foo', NS_CATEGORY );

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$store->expects( $this->once() )
			->method( 'getPropertySubjects' )
			->with(
				$this->equalTo( new DIProperty( '_SUBC' ) ),
				$this->equalTo( $category ),
				$this->anything() )
			->will( $this->returnValue( array( DIWikiPage::newFromText( 'Bar', NS_CATEGORY ) ) ) );

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$cache->expects( $this->once() )
			->method( 'contains' )
			->will( $this->returnValue( false ) );

		$cache->expects( $this->once() )
			->method( 'save' )
			->with(
				$this->equalTo( '_SUBC#Foo#b3ed5707ed115aa0ef22f567c08c35df' ),
				$this->anything() );

		$instance = new PropertyHierarchyLookup( $store, $cache );

		$expected = array(
			DIWikiPage::newFromText( 'Bar', NS_CATEGORY )
		);

		$this->assertEquals(
			$expected,
			$instance->findSubcategoryListFor( $category )
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

		$instance = new PropertyHierarchyLookup( $store, $cache );
		$instance->setSubpropertyDepth( 0 );

		$this->assertFalse(
			$instance->hasSubpropertyFor( new DIProperty( 'Foo' ) )
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

		$instance = new PropertyHierarchyLookup( $store, $cache );
		$instance->setSubcategoryDepth( 0 );

		$this->assertFalse(
			$instance->hasSubcategoryFor( DIWikiPage::newFromText( 'Foo', NS_CATEGORY ) )
		);
	}

}
