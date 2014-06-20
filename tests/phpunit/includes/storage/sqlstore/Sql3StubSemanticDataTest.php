<?php

namespace SMW\Tests\SQLStore;

use SMW\StoreFactory;
use SMW\SemanticData;
use SMW\DIWikiPage;
use SMW\DIProperty;
use SMWDITime as DITime;
use SMWSql3StubSemanticData;

use Title;

/**
 * @covers \SMWSql3StubSemanticData
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 1.9.0.2
 *
 * @author mwjames
 */
class Sql3StubSemanticDataTest extends \PHPUnit_Framework_TestCase {

	/** @var Store */
	private $store;

	protected function setUp() {
		$this->store = StoreFactory::getStore();
	}

	public function testCanConstruct() {

		$store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getSubject' )
			->will( $this->returnValue( DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) ) ) );

		$this->assertInstanceOf(
			'\SMWSql3StubSemanticData',
			SMWSql3StubSemanticData::newFromSemanticData( $semanticData, $store )
		);
	}

	public function testGetPropertyValues() {

		if ( !$this->store instanceOf \SMWSQLStore3 ) {
			$this->markTestSkipped( "Requires a SMWSQLStore3 instance" );
		}

		$instance = SMWSql3StubSemanticData::newFromSemanticData(
			new SemanticData( DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) ) ),
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
	 * @dataProvider removePropertyObjectProvider
	 */
	public function testRemovePropertyObjectValue( $title, $property, $dataItem ) {

		if ( !$this->store instanceOf \SMWSQLStore3 ) {
			$this->markTestSkipped( "Requires a SMWSQLStore3 instance" );
		}

		$instance = SMWSql3StubSemanticData::newFromSemanticData(
			new SemanticData( DIWikiPage::newFromTitle( $title ) ),
			$this->store
		);

		$instance->addPropertyObjectValue( $property, $dataItem );
		$this->assertFalse( $instance->isEmpty() );

		$instance->removePropertyObjectValue( $property, $dataItem );
		$this->assertTrue( $instance->isEmpty() );
	}

	/**
	 * @return array
	 */
	public function removePropertyObjectProvider() {

		$provider = array();

		$title = Title::newFromText( 'Foo' );

		// #0
		$provider[] = array(
			$title,
			new DIProperty( '_MDAT' ),
			DITime::newFromTimestamp( 1272508903 )
		);

		return $provider;
	}

}
