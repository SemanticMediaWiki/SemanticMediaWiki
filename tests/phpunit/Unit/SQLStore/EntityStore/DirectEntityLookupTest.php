<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\SQLStore\EntityStore\DirectEntityLookup;

/**
 * @covers \SMW\SQLStore\EntityStore\DirectEntityLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class DirectEntityLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			'\SMW\SQLStore\EntityStore\DirectEntityLookup',
			new DirectEntityLookup( $store )
		);
	}

	public function testGetSemanticData() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$reader = $this->getMockBuilder( '\SMWSQLStore3Readers' )
			->disableOriginalConstructor()
			->getMock();

		$reader->expects( $this->once() )
			->method( 'getSemanticData' )
			->with(
				$this->equalTo( $subject ),
				$this->anything() );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getReader' ) )
			->getMock();

		$store->expects( $this->once() )
			->method( 'getReader' )
			->will( $this->returnValue( $reader ) );

		$instance = new DirectEntityLookup(
			$store
		);

		$instance->getSemanticData( $subject );
	}

	public function testGetProperties() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$reader = $this->getMockBuilder( '\SMWSQLStore3Readers' )
			->disableOriginalConstructor()
			->getMock();

		$reader->expects( $this->once() )
			->method( 'getProperties' )
			->with(
				$this->equalTo( $subject ),
				$this->anything() );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getReader' ) )
			->getMock();

		$store->expects( $this->once() )
			->method( 'getReader' )
			->will( $this->returnValue( $reader ) );

		$instance = new DirectEntityLookup(
			$store
		);

		$instance->getProperties( $subject );
	}

	public function testGetPropertyValues() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );
		$property = new DIProperty( 'Bar' );

		$reader = $this->getMockBuilder( '\SMWSQLStore3Readers' )
			->disableOriginalConstructor()
			->getMock();

		$reader->expects( $this->once() )
			->method( 'getPropertyValues' )
			->with(
				$this->equalTo( $subject ),
				$this->equalTo( $property ),
				$this->anything() );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getReader' ) )
			->getMock();

		$store->expects( $this->once() )
			->method( 'getReader' )
			->will( $this->returnValue( $reader ) );

		$instance = new DirectEntityLookup(
			$store
		);

		$instance->getPropertyValues( $subject, $property );
	}

	public function testGetPropertySubjects() {

		$property = new DIProperty( 'Bar' );
		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$reader = $this->getMockBuilder( '\SMWSQLStore3Readers' )
			->disableOriginalConstructor()
			->getMock();

		$reader->expects( $this->once() )
			->method( 'getPropertySubjects' )
			->with(
				$this->equalTo( $property ),
				$this->equalTo( $subject ),
				$this->anything() );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getReader' ) )
			->getMock();

		$store->expects( $this->once() )
			->method( 'getReader' )
			->will( $this->returnValue( $reader ) );

		$instance = new DirectEntityLookup(
			$store
		);

		$instance->getPropertySubjects( $property, $subject );
	}

	public function testGetAllPropertySubjects() {

		$property = new DIProperty( 'Bar' );

		$reader = $this->getMockBuilder( '\SMWSQLStore3Readers' )
			->disableOriginalConstructor()
			->getMock();

		$reader->expects( $this->once() )
			->method( 'getAllPropertySubjects' )
			->with( $this->equalTo( $property ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getReader' ) )
			->getMock();

		$store->expects( $this->once() )
			->method( 'getReader' )
			->will( $this->returnValue( $reader ) );

		$instance = new DirectEntityLookup(
			$store
		);

		$instance->getAllPropertySubjects( $property );
	}

	public function testGetInProperties() {

		$subject = new DIWikiPage( 'Foo', NS_MAIN );

		$reader = $this->getMockBuilder( '\SMWSQLStore3Readers' )
			->disableOriginalConstructor()
			->getMock();

		$reader->expects( $this->once() )
			->method( 'getInProperties' )
			->with( $this->equalTo( $subject ) );

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->setMethods( array( 'getReader' ) )
			->getMock();

		$store->expects( $this->once() )
			->method( 'getReader' )
			->will( $this->returnValue( $reader ) );

		$instance = new DirectEntityLookup(
			$store
		);

		$instance->getInProperties( $subject );
	}

}
