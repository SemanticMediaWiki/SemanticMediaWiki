<?php

namespace SMW\Test\SQLStore;

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
 * @licence GNU GPL v2+
 * @since 1.9.0.2
 *
 * @author mwjames
 */
class Sql3StubSemanticDataTest extends \SMW\Test\SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return 'SMWSql3StubSemanticData';
	}

	/**
	 * @return SMWSql3StubSemanticData
	 */
	protected function newInstance( SemanticData $semanticData = null ) {

		if ( $semanticData === null ) {
			$semanticData = $this->newMockBuilder()->newObject( 'SemanticData', array(
				'getSubject' => $this->newMockBuilder()->newObject( 'DIWikiPage' )
			) );
		}

		return SMWSql3StubSemanticData::newFromSemanticData( $semanticData, $this->getStore() );
	}

	/**
	 * @since 1.9.0.2
	 */
	public function testCanConstruct() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9.0.2
	 */
	public function testGetPropertyValues() {

		$instance = $this->newInstance( new SemanticData( DIWikiPage::newFromTitle( Title::newFromText( 'Foo' ) ) ) );

		$this->assertInstanceOf( 'SMW\DIWikiPage', $instance->getSubject() );

		$this->assertTrue(
			$instance->getPropertyValues( new DIProperty( 'Foo', true ) ) === array() ,
			'Asserts that an inverse Property returns an empty array'
		);

		$this->assertTrue(
			$instance->getPropertyValues( new DIProperty( 'Foo' ) ) === array() ,
			'Asserts that an unknown Property returns an empty array'
		);

	}

	/**
	 * @dataProvider removePropertyObjectProvider
	 *
	 * @since 1.9.0.2
	 */
	public function testRemovePropertyObjectValue( $title, $property, $dataItem ) {

		$instance = $this->newInstance( new SemanticData( DIWikiPage::newFromTitle( $title ) ) );
		$instance->addPropertyObjectValue( $property, $dataItem );

		$this->assertFalse(
			$instance->isEmpty() ,
			'Asserts that isEmpty() returns false'
		);

		$instance->removePropertyObjectValue( $property, $dataItem );

		$this->assertTrue(
			$instance->isEmpty() ,
			'Asserts that isEmpty() returns true'
		);

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
			new DIProperty( '_MDAT'),
			DITime::newFromTimestamp( 1272508903 )
		);

		return $provider;
	}

}
