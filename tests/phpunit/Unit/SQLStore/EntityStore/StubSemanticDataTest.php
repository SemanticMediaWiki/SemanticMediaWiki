<?php

namespace SMW\Tests\Unit\SQLStore\EntityStore;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\Time;
use SMW\DataItems\WikiPage;
use SMW\DataModel\SemanticData;
use SMW\SQLStore\EntityStore\StubSemanticData;
use SMW\SQLStore\SQLStore;
use SMW\Tests\TestEnvironment;

/**
 * @covers SMW\SQLStore\EntityStore\StubSemanticData
 *
 * @license GPL-2.0-or-later
 * @since 1.9
 *
 * @author mwjames
 */
class StubSemanticDataTest extends TestCase {

	private $store;
	private $testEnvironment;

	protected function setUp(): void {
		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( SQLStore::class )
			->disableOriginalConstructor()
			->getMock();

		$this->store->expects( $this->any() )
			->method( 'getRedirectTarget' )
			->willReturnArgument( 0 );

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
	}

	public function testCanConstruct() {
		$subject = WikiPage::newFromText( __METHOD__ );

		$semanticData = $this->getMockBuilder( SemanticData::class )
			->setConstructorArgs( [ WikiPage::newFromText( 'Foo' ) ] )
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getSubject' )
			->willReturn( $subject );

		$this->assertInstanceOf(
			StubSemanticData::class,
			StubSemanticData::newFromSemanticData( $semanticData, $this->store )
		);
	}

	public function testNotToResolveSubobjectsForRedirect() {
		$instance = $this->getMockBuilder( StubSemanticData::class )
			->setConstructorArgs( [
				WikiPage::newFromText( __METHOD__ ),
				$this->store ] )
			->setMethods( [
				'getProperties',
				'isRedirect',
				'getPropertyValues' ] )
			->getMock();

		$instance->expects( $this->once() )
			->method( 'getProperties' )
			->willReturn( [ new Property( '_SOBJ' ) ] );

		$instance->expects( $this->once() )
			->method( 'isRedirect' )
			->willReturn( true );

		$instance->expects( $this->never() )
			->method( 'getPropertyValues' );

		$instance->getSubSemanticData();
	}

	public function testGetPropertyValues() {
		$instance = StubSemanticData::newFromSemanticData(
			new SemanticData( WikiPage::newFromText( __METHOD__ ) ),
			$this->store
		);

		$this->assertInstanceOf(
			WikiPage::class,
			$instance->getSubject()
		);

		$this->assertEmpty(
			$instance->getPropertyValues( new Property( 'unknownInverseProperty', true ) )
		);

		$this->assertEmpty(
			$instance->getPropertyValues( new Property( 'unknownProperty' ) )
		);
	}

	/**
	 * @dataProvider propertyObjectProvider
	 */
	public function testPhpSerialization( $property, $dataItem ) {
		$instance = StubSemanticData::newFromSemanticData(
			new SemanticData( new WikiPage( 'Foo', NS_MAIN ) ),
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
			new SemanticData( new WikiPage( 'Foo', NS_MAIN ) ),
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
			new Property( '_MDAT' ),
			Time::newFromTimestamp( 1272508903 )
		];

		return $provider;
	}

}
