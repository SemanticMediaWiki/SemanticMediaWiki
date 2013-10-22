<?php

namespace SMW\Test;

use SMW\DataValueFactory;
use SMW\SemanticData;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Subobject;

use SMWDITime as DITime;

use Title;

/**
 * @covers \SMW\SemanticData
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SemanticDataTest extends SemanticMediaWikiTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SemanticData';
	}

	/**
	 * @since 1.9
	 *
	 * @return SemanticData
	 */
	private function newInstance( Title $title = null ) {

		if ( $title === null ) {
			$title = $this->newTitle();
		}

		return new SemanticData( DIWikiPage::newFromTitle( $title ) );
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
		$this->assertInstanceOf( 'SMWSemanticData', $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testGetPropertyValues() {

		$instance = $this->newInstance();

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
	 * @since 1.9
	 */
	public function testaddPropertyValue() {

		$instance = $this->newInstance();
		$instance->addPropertyValue( 'Foo', DITime::newFromTimestamp( 1272508903 ) );

		// !!! THANKS for the GLOBAL dependency within addPropertyValue() !!!
		$key = $GLOBALS['wgContLang']->getNsText( SMW_NS_PROPERTY ) . ':' . 'Foo';

		foreach ( $instance->getProperties() as $property ) {

			$this->assertInstanceOf(
				'\SMW\DIProperty',
				$property,
				'Asserts that a DIProperty instance is returned'
			);

			$this->assertEquals(
				$key,
				$property->getKey() ,
				'Asserts that both keys are equal'
			);
		}

		$expected = array(
			'propertyCount' => 1,
			'propertyLabel' => array( $key ),
			'propertyValue' => array( '2010-04-29T02:41:43' )
		);

		$this->assertSemanticData( $instance, $expected );

	}

	/**
	 * @since 1.9
	 */
	public function testGetHash() {

		$title = $this->newTitle();
		$instance = $this->newInstance( $title );
		$instance->addDataValue( DataValueFactory::newPropertyValue( 'Has fooQuex', 'Bar' ) );

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo' );
		$subobject->addDataValue( DataValueFactory::newPropertyValue( 'Has subobjects', 'Bam' ) );

		$instance->addPropertyObjectValue( $subobject->getProperty(), $subobject->getContainer() );

		$this->assertInternalType(
			'string',
			$instance->getHash() ,
			'Asserts that getHash() return a string'
		);

	}

	/**
	 * @since 1.9
	 */
	public function testGetSubSemanticData() {

		$title = $this->newTitle();
		$instance = $this->newInstance( $title );

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo' );
		$subobject->addDataValue( DataValueFactory::newPropertyValue( 'Has subobjects', 'Bam' ) );

		// Adds only a subobject reference to the container
		$instance->addPropertyObjectValue( $subobject->getProperty(), $subobject->getContainer() );

		$this->assertNotInstanceOf(
			'SMWContainerSemanticData',
			$instance->getSubSemanticData() ,
			'Asserts that getSubSemanticData() does not return a SMWContainerSemanticData instance'
		);

		// Adds a complete container
		$instance->addPropertyObjectValue( $subobject->getProperty(), $subobject->getSemanticData()->getSubject() );

		foreach ( $instance->getSubSemanticData() as $subSemanticData ) {

			$this->assertInstanceOf(
				'SMWContainerSemanticData',
				$subSemanticData,
				'Asserts that getSubSemanticData() returns a SMWContainerSemanticData instance'
			);

		}

	}

	/**
	 * @since 1.9
	 */
	public function testAddAndRemoveSubSemanticData() {

		$title = $this->newTitle();
		$instance = $this->newInstance( $title );

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo' );
		$subobject->addDataValue( DataValueFactory::newPropertyValue( 'Has subobjects', 'Bam' ) );

		// Adds only a subobject reference to the container
		$instance->addPropertyObjectValue( $subobject->getProperty(), $subobject->getContainer() );

		$this->assertNotInstanceOf(
			'SMWContainerSemanticData',
			$instance->getSubSemanticData() ,
			'Asserts that getSubSemanticData() does not return a SMWContainerSemanticData instance'
		);

		$instance->addSubSemanticData( $subobject->getSemanticData() );

		foreach ( $instance->getSubSemanticData() as $subSemanticData ) {

			$this->assertInstanceOf(
				'SMWContainerSemanticData',
				$subSemanticData,
				'Asserts that getSubSemanticData() returns a SMWContainerSemanticData instance'
			);

			$this->assertEquals(
				$subSemanticData,
				$subobject->getSemanticData(),
				'Asserts that both SemanticData containers are equal'
			);

		}

		$instance->removeSubSemanticData( $subobject->getSemanticData() );

		$this->assertNotInstanceOf(
			'SMWContainerSemanticData',
			$instance->getSubSemanticData() ,
			'Asserts that getSubSemanticData() does not return a SMWContainerSemanticData instance'
		);

	}

	/**
	 * @since 1.9
	 */
	public function testVisibility() {

		$title = $this->newTitle();
		$instance = $this->newInstance( $title );
		$instance->addDataValue( DataValueFactory::newPropertyValue( 'Has fooQuex', 'Bar' ) );

		$this->assertTrue(
			$instance->hasVisibleProperties() ,
			'Asserts that hasVisibleProperties() returns true'
		);

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo' );
		$subobject->addDataValue( DataValueFactory::newPropertyValue( 'Has subobjects', 'Bam' ) );

		$instance->addPropertyObjectValue( $subobject->getProperty(), $subobject->getContainer() );

		$this->assertTrue(
			$instance->hasVisibleSpecialProperties() ,
			'Asserts that hasVisibleSpecialProperties() returns true'
		);

	}

	/**
	 * @since 1.9
	 */
	public function testRemovePropertyObjectValue() {

		$instance = $this->newInstance();
		$instance->addPropertyObjectValue( new DIProperty( '_MDAT'), DITime::newFromTimestamp( 1272508903 ) );

		$this->assertFalse(
			$instance->isEmpty() ,
			'Asserts that isEmpty() returns false'
		);

		$instance->removePropertyObjectValue( new DIProperty( '_MDAT'), DITime::newFromTimestamp( 1272508903 ) );

		$this->assertTrue(
			$instance->isEmpty() ,
			'Asserts that isEmpty() returns true'
		);

	}

	/**
	 * @since 1.9
	 */
	public function testClear() {

		$instance = $this->newInstance();
		$instance->addPropertyObjectValue( new DIProperty( '_MDAT'), DITime::newFromTimestamp( 1272508903 ) );

		$this->assertFalse(
			$instance->isEmpty() ,
			'Asserts that isEmpty() returns false'
		);

		$instance->clear();

		$this->assertTrue(
			$instance->isEmpty() ,
			'Asserts that isEmpty() returns true'
		);

	}

	/**
	 * @dataProvider dataValueDataProvider
	 *
	 * @since 1.9
	 */
	public function testAddDataValue( $dataValues, $expected ) {

		$instance = $this->newInstance();

		foreach ( $dataValues as $dataValue ) {
			$instance->addDataValue( $dataValue );
		}

		if ( $expected['error'] === 0 ) {
			$this->assertSemanticData( $instance, $expected );
		} else {
			$this->assertCount( $expected['error'], $instance->getErrors() );
		}
	}

	/**
	 * @return array
	 */
	public function dataValueDataProvider() {

		$provider = array();

		// #0 Single DataValue is added
		$provider[] = array(
			array(
				DataValueFactory::newPropertyValue( 'Foo', 'Bar' ),
			),
			array(
				'error'         => 0,
				'propertyCount' => 1,
				'propertyLabel' => 'Foo',
				'propertyValue' => 'Bar'
			)
		);

		// #1 Equal Datavalues will only result in one added object
		$provider[] = array(
			array(
				DataValueFactory::newPropertyValue( 'Foo', 'Bar' ),
				DataValueFactory::newPropertyValue( 'Foo', 'Bar' ),
			),
			array(
				'error'         => 0,
				'propertyCount' => 1,
				'propertyLabel' => 'Foo',
				'propertyValue' => 'Bar'
			)
		);

		// #2 Two different DataValue objects
		$provider[] = array(
			array(
				DataValueFactory::newPropertyValue( 'Foo', 'Bar' ),
				DataValueFactory::newPropertyValue( 'Lila', 'Lula' ),
			),
			array(
				'error'         => 0,
				'propertyCount' => 2,
				'propertyLabel' => array( 'Foo', 'Lila' ),
				'propertyValue' => array( 'Bar', 'Lula' )
			)
		);

		// #3 Error (Inverse)
		$provider[] = array(
			array(
				DataValueFactory::newPropertyValue( '-Foo', 'Bar' ),
			),
			array(
				'error'         => 1,
				'propertyCount' => 0,
			)
		);

		// #4 One valid DataValue + an error object
		$provider[] = array(
			array(
				DataValueFactory::newPropertyValue( 'Foo', 'Bar' ),
				DataValueFactory::newPropertyValue( '-Foo', 'bar' ),
			),
			array(
				'error'         => 1,
				'propertyCount' => 1,
				'propertyLabel' => array( 'Foo' ),
				'propertyValue' => array( 'Bar' )
			)
		);


		// #5 Error (Predefined)
		$provider[] = array(
			array(
				DataValueFactory::newPropertyValue( '_Foo', 'Bar' ),
			),
			array(
				'error'         => 1,
				'propertyCount' => 0,
			)
		);

		// #6 Error (Known predefined property)
		$provider[] = array(
			array(
				DataValueFactory::newPropertyValue( 'Modification date', 'Bar' ),
			),
			array(
				'error'         => 1,
				'propertyCount' => 0,
			)
		);

		return $provider;
	}

}
