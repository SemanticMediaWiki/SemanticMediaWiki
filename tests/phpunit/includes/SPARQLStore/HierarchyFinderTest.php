<?php

namespace SMW\Tests\SPARQLStore;

use SMW\SPARQLStore\HierarchyFinder;
use SMW\DIProperty;

/**
 * @covers \SMW\SPARQLStore\HierarchyFinder
 *
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.3
 *
 * @author mwjames
 */
class HierarchyFinderTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SPARQLStore\SPARQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$cache = $this->getMockBuilder( '\Onoi\Cache\Cache' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\SPARQLStore\HierarchyFinder',
			new HierarchyFinder( $store, $cache )
		);
	}

	public function testVerifyForSubpropertyOnNonCached() {

		$store = $this->getMockBuilder( '\SMW\SPARQLStore\SPARQLStore' )
			->disableOriginalConstructor()
			->getMock();

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
				$this->equalTo( 'Foo' ),
				$this->equalTo( false ) );

		$instance = new HierarchyFinder( $store, $cache );

		$this->assertInternalType(
			'boolean',
			$instance->hasSubpropertyFor( new DIProperty( 'Foo' ) )
		);
	}

}
