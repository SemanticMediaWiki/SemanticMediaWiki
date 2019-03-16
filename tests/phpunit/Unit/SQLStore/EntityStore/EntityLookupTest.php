<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SQLStore\EntityStore\EntityLookup;

/**
 * @covers \SMW\SQLStore\EntityStore\EntityLookup
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class EntityLookupTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$this->assertInstanceOf(
			EntityLookup::class,
			new EntityLookup( $store )
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
			->setMethods( [ 'getReader' ] )
			->getMock();

		$store->expects( $this->once() )
			->method( 'getReader' )
			->will( $this->returnValue( $reader ) );

		$instance = new EntityLookup(
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
			->setMethods( [ 'getReader' ] )
			->getMock();

		$store->expects( $this->once() )
			->method( 'getReader' )
			->will( $this->returnValue( $reader ) );

		$instance = new EntityLookup(
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
			->setMethods( [ 'getReader' ] )
			->getMock();

		$store->expects( $this->once() )
			->method( 'getReader' )
			->will( $this->returnValue( $reader ) );

		$instance = new EntityLookup(
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
			->setMethods( [ 'getReader' ] )
			->getMock();

		$store->expects( $this->once() )
			->method( 'getReader' )
			->will( $this->returnValue( $reader ) );

		$instance = new EntityLookup(
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
			->setMethods( [ 'getReader' ] )
			->getMock();

		$store->expects( $this->once() )
			->method( 'getReader' )
			->will( $this->returnValue( $reader ) );

		$instance = new EntityLookup(
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
			->setMethods( [ 'getReader' ] )
			->getMock();

		$store->expects( $this->once() )
			->method( 'getReader' )
			->will( $this->returnValue( $reader ) );

		$instance = new EntityLookup(
			$store
		);

		$instance->getInProperties( $subject );
	}

}
