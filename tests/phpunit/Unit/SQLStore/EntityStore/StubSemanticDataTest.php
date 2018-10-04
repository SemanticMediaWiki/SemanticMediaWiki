<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\SemanticData;
use SMW\SQLStore\EntityStore\StubSemanticData;
use SMW\Tests\TestEnvironment;
use SMWDITime as DITime;

/**
 * @covers SMW\SQLStore\EntityStore\StubSemanticData
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class StubSemanticDataTest extends \PHPUnit_Framework_TestCase {

	private $store;
	private $testEnvironment;

	protected function setUp() {

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\SQLStore\SQLStore' )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getRedirectTarget' )
			->will( $this->returnArgument( 0 ) );

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {

		$subject = DIWikiPage::newFromText( __METHOD__ );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$this->assertInstanceOf(
			StubSemanticData::class,
			StubSemanticData::newFromSemanticData( $semanticData, $this->store )
		);
	}

	public function testNotToResolveSubobjectsForRedirect() {

		$instance = $this->getMockBuilder( StubSemanticData::class )
			->setConstructorArgs( [
				DIWikiPage::newFromText( __METHOD__ ),
				$this->store ] )
			->setMethods( [
				'getProperties',
				'isRedirect',
				'getPropertyValues' ] )
			->getMock();

		$instance->expects( $this->once() )
			->method( 'getProperties' )
			->will( $this->returnValue( [ new DIProperty( '_SOBJ' ) ] ) );

		$instance->expects( $this->once() )
			->method( 'isRedirect' )
			->will( $this->returnValue( true ) );

		$instance->expects( $this->never() )
			->method( 'getPropertyValues' );

		$instance->getSubSemanticData();
	}

	public function testGetPropertyValues() {

		$instance = StubSemanticData::newFromSemanticData(
			new SemanticData( DIWikiPage::newFromText( __METHOD__ ) ),
			$this->store
		);

		$this->assertInstanceOf(
			'SMW\DIWikiPage',
			$instance->getSubject()
		);

		$this->assertEmpty(
			$instance->getPropertyValues( new DIProperty( 'unknownInverseProperty', true ) )
		);

		$this->assertEmpty(
			$instance->getPropertyValues( new DIProperty( 'unknownProperty' ) )
		);
	}

	/**
	 * @dataProvider propertyObjectProvider
	 */
	public function testPhpSerialization( $property, $dataItem ) {

		$instance = StubSemanticData::newFromSemanticData(
			new SemanticData( new DIWikiPage( 'Foo', NS_MAIN ) ),
			$this->store
		);

		$instance->addPropertyObjectValue(
			$property,
			$dataItem
		);

		$serialization = serialize( $instance );

		$this->assertEquals(
			$instance->getHash(),
			unserialize( $serialization )->getHash()
		);
	}

	/**
	 * @dataProvider propertyObjectProvider
	 */
	public function testRemovePropertyObjectValue( $property, $dataItem ) {

		$instance = StubSemanticData::newFromSemanticData(
			new SemanticData( new DIWikiPage( 'Foo', NS_MAIN ) ),
			$this->store
		);

		$instance->addPropertyObjectValue( $property, $dataItem );
		$this->assertFalse( $instance->isEmpty() );

		$instance->removePropertyObjectValue( $property, $dataItem );
		$this->assertTrue( $instance->isEmpty() );
	}

	public function propertyObjectProvider() {

		$provider = [];

		// #0
		$provider[] = [
			new DIProperty( '_MDAT' ),
			DITime::newFromTimestamp( 1272508903 )
		];

		return $provider;
	}

}
