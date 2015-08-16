<?php

namespace SMW\Tests;

use SMW\PropertyHierarchyExaminer;
use SMW\DIProperty;

/**
 * @covers \SMW\PropertyHierarchyExaminer
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class PropertyHierarchyExaminerTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\PropertyHierarchyExaminer',
			new PropertyHierarchyExaminer( $store, $cache )
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
				$this->equalTo( '_SUBP#Foo' ),
				$this->equalTo( false ) );

		$instance = new PropertyHierarchyExaminer( $store, $cache );

		$this->assertInternalType(
			'boolean',
			$instance->hasSubpropertyFor( new DIProperty( 'Foo' ) )
		);
	}

}
