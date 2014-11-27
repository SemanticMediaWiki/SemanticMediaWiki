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

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$semanticData->expects( $this->once() )
			->method( 'getSubject' )
			->will( $this->returnValue( DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) ) ) );

		$this->assertInstanceOf(
			'\SMWSql3StubSemanticData',
			StubSemanticData::newFromSemanticData( $semanticData, $this->store )
		);
	}

	public function testCheckRedirectInfoForSubject() {

		$subject = DIWikiPage::newFromTitle( Title::newFromText( __METHOD__ ) );

		$smwIds = $this->getMockBuilder( '\SMWSql3SmwIds' )
			->disableOriginalConstructor()
			->getMock();

		$smwIds->expects( $this->once() )
			->method( 'isSubjectRedirect' )
			->will( $this->returnValue( false ) );

		$this->store->expects( $this->once() )
			->method( 'getObjectIds' )
			->will( $this->returnValue( $smwIds ) );

		$stubSemanticData = new StubSemanticData( $subject, $this->store );

		$this->assertFalse(
			$stubSemanticData->isRedirect()
		);
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
