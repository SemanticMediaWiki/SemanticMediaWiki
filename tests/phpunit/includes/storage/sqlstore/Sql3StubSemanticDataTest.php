<?php

namespace SMW\Tests\SQLStore;

use SMW\SemanticData;
use SMW\DIWikiPage;
use SMW\DIProperty;

use SMWDITime as DITime;
use SMWSql3StubSemanticData as StubSemanticData;

use Title;

/**
 * @covers \SMWSql3StubSemanticData
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

	private $store;

	protected function setUp() {

		$this->store = $this->getMockBuilder( '\SMWSQLStore3' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getSubject' )
			->will( $this->returnValue( $subject ) );

		$this->assertInstanceOf(
			'\SMWSql3StubSemanticData',
			StubSemanticData::newFromSemanticData( $semanticData, $this->store )
		);
	}

	public function testNotToResolveSubobjectsForRedirect() {

		$instance = $this->getMockBuilder( '\SMWSql3StubSemanticData' )
			->disableOriginalConstructor()
			->setMethods( array(
				'getProperties',
				'isRedirect',
				'getPropertyValues' ) )
			->getMock();

		$instance->expects( $this->once() )
			->method( 'getProperties' )
			->will( $this->returnValue( array( new DIProperty( '_SOBJ' ) ) ) );

		$instance->expects( $this->once() )
			->method( 'isRedirect' )
			->will( $this->returnValue( true ) );

		$instance->expects( $this->never() )
			->method( 'getPropertyValues' );

		$instance->getSubSemanticData();
	}

	public function testGetPropertyValues() {

		$instance = StubSemanticData::newFromSemanticData(
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

		$instance = StubSemanticData::newFromSemanticData(
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
