<?php

namespace SMW\Test;

use SMW\Configuration\Configuration;
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
	 * @return Subobject
	 */
	private function newSubobject( Title $title, $property = 'Quuy', $value = 'Xeer' ) {

		$subobject = new Subobject( $title );
		$subobject->setSemanticData( 'Foo' );
		$subobject->addDataValue( DataValueFactory::getInstance()->newPropertyValue( $property, $value ) );

		return $subobject;
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
		$instance->addPropertyValue( 'FuyuQuy', DIWikiPage::doUnserialize( 'Foo#0#' ) );

		$key = Configuration::getInstance()->get( 'wgContLang' )->getNsText( SMW_NS_PROPERTY ) . ':' . 'FuyuQuy';

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
			'propertyCount'  => 1,
			'propertyLabels' => array( $key ),
			'propertyValues' => array( 'Foo' )
		);

		$semanticDataValidator = new SemanticDataValidator;
		$semanticDataValidator->assertThatPropertiesAreSet( $expected, $instance );

	}

	/**
	 * @since 1.9
	 */
	public function testGetHash() {

		$title = $this->newTitle();
		$instance = $this->newInstance( $title );
		$instance->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' ) );

		$subobject = $this->newSubobject( $title );
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

		// Adds only a subobject reference to the container
		$subobject = $this->newSubobject( $title );
		$instance->addPropertyObjectValue( $subobject->getProperty(), $subobject->getSemanticData()->getSubject() );

		$this->assertNotInstanceOf(
			'SMWContainerSemanticData',
			$instance->getSubSemanticData() ,
			'Asserts that getSubSemanticData() does not return a SMWContainerSemanticData instance'
		);

		// Adds a complete container
		$instance->addPropertyObjectValue( $subobject->getProperty(), $subobject->getContainer() );

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
	public function testImportDataFromWithDifferentSubjectMWException() {

		$this->setExpectedException( 'MWException' );
		$this->newInstance()->importDataFrom( $this->newInstance() );

	}

	/**
	 * @since 1.9
	 */
	public function testAddAndRemoveSubSemanticData() {

		$title = $this->newTitle();
		$instance = $this->newInstance( $title );

		// Adds only a subobject reference to the container
		$subobject = $this->newSubobject( $title );
		$instance->addPropertyObjectValue( $subobject->getProperty(), $subobject->getSemanticData()->getSubject() );

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
	public function testAddSubSemanticDataWithOutSubobjectNameMWException() {

		$this->setExpectedException( 'MWException' );
		$this->newInstance()->addSubSemanticData( $this->newInstance() );

	}

	/**
	 * @since 1.9
	 */
	public function testAddSubSemanticDataWithDifferentKeyMWException() {

		$this->setExpectedException( 'MWException' );
		$this->newInstance()->addSubSemanticData(
			$this->newSubobject( $this->newTitle() )->getSemanticData()
		);

	}

	/**
	 * @since 1.9
	 */
	public function testHasAndFindSubSemanticData() {

		$title = $this->newTitle();
		$instance = $this->newInstance( $title );

		$subobject = $this->newSubobject( $title );
		$subobjectName = $subobject->getSemanticData()->getSubject()->getSubobjectName();

		$this->assertFalse(
			$instance->hasSubSemanticData() ,
			'Asserts that hasSubSemanticData() returns false'
		);

		$this->assertEmpty(
			$instance->findSubSemanticData( $subobjectName ),
			'Asserts that findSubSemanticData() returns empty'
		);

		// Adds only a subobject reference to the container
		$instance->addPropertyObjectValue( $subobject->getProperty(), $subobject->getSemanticData()->getSubject() );

		$this->assertFalse(
			$instance->hasSubSemanticData( $subobjectName ),
			'Asserts that hasSubSemanticData() returns false'
		);

		$this->assertEmpty(
			$instance->findSubSemanticData( $subobjectName ),
			'Asserts that findSubSemanticData() returns empty'
		);

		$instance->addSubSemanticData( $subobject->getSemanticData() );

		$this->assertTrue(
			$instance->hasSubSemanticData( $subobjectName ),
			'Asserts that hasSubSemanticData() returns true'
		);

		$this->assertNotEmpty(
			$instance->findSubSemanticData( $subobjectName ),
			'Asserts that findSubSemanticData() returns not empty'
		);

		$this->assertInstanceOf(
			'SMWContainerSemanticData',
			$instance->findSubSemanticData( $subobjectName ),
			'Asserts that findSubSemanticData() does return a SMWContainerSemanticData instance'
		);

	}

	/**
	 * @since 1.9
	 */
	public function testHasSubSemanticDataOnNonStringSubobjectName() {

		$this->assertFalse(
			$this->newInstance()->hasSubSemanticData( new \stdClass ),
			'Asserts that hasSubSemanticData() returns false'
		);

	}

	/**
	 * @since 1.9
	 */
	public function testFindSubSemanticDataOnNonStringSubobjectName() {

		$this->assertEmpty(
			$this->newInstance()->findSubSemanticData( new \stdClass ),
			'Asserts that findSubSemanticData() returns an empty array'
		);

	}

	/**
	 * @since 1.9
	 */
	public function testVisibility() {

		$title = $this->newTitle();
		$instance = $this->newInstance( $title );
		$instance->addDataValue( DataValueFactory::getInstance()->newPropertyValue( 'Has fooQuex', 'Bar' ) );

		$this->assertTrue(
			$instance->hasVisibleProperties() ,
			'Asserts that hasVisibleProperties() returns true'
		);

		$subobject = $this->newSubobject( $title );
		$instance->addPropertyObjectValue( $subobject->getProperty(), $subobject->getContainer() );

		$this->assertTrue(
			$instance->hasVisibleSpecialProperties() ,
			'Asserts that hasVisibleSpecialProperties() returns true'
		);

	}

	/**
	 * @dataProvider removePropertyObjectProvider
	 *
	 * @since 1.9
	 */
	public function testRemovePropertyObjectValue( $title, $property, $dataItem ) {

		$instance = $this->newInstance( $title );
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
			$semanticDataValidator = new SemanticDataValidator;
			$semanticDataValidator->assertThatPropertiesAreSet( $expected, $instance );
		} else {
			$this->assertCount( $expected['error'], $instance->getErrors() );
		}
	}

	/**
	 * @return array
	 */
	public function removePropertyObjectProvider() {

		$provider = array();

		$title = $this->newTitle();
		$subobject = $this->newSubobject( $title );

		// #0
		$provider[] = array(
			$title,
			new DIProperty( '_MDAT'),
			DITime::newFromTimestamp( 1272508903 )
		);

		// #1
		$provider[] = array(
			$title,
			$subobject->getProperty(),
			$subobject->getContainer()
		);

		return $provider;
	}

	/**
	 * @return array
	 */
	public function dataValueDataProvider() {

		$provider = array();

		// #0 Single DataValue is added
		$provider[] = array(
			array(
				DataValueFactory::getInstance()->newPropertyValue( 'Foo', 'Bar' ),
			),
			array(
				'error'         => 0,
				'propertyCount' => 1,
				'propertyLabels' => 'Foo',
				'propertyValues' => 'Bar'
			)
		);

		// #1 Equal Datavalues will only result in one added object
		$provider[] = array(
			array(
				DataValueFactory::getInstance()->newPropertyValue( 'Foo', 'Bar' ),
				DataValueFactory::getInstance()->newPropertyValue( 'Foo', 'Bar' ),
			),
			array(
				'error'         => 0,
				'propertyCount' => 1,
				'propertyLabels' => 'Foo',
				'propertyValues' => 'Bar'
			)
		);

		// #2 Two different DataValue objects
		$provider[] = array(
			array(
				DataValueFactory::getInstance()->newPropertyValue( 'Foo', 'Bar' ),
				DataValueFactory::getInstance()->newPropertyValue( 'Lila', 'Lula' ),
			),
			array(
				'error'         => 0,
				'propertyCount' => 2,
				'propertyLabels' => array( 'Foo', 'Lila' ),
				'propertyValues' => array( 'Bar', 'Lula' )
			)
		);

		// #3 Error (Inverse)
		$provider[] = array(
			array(
				DataValueFactory::getInstance()->newPropertyValue( '-Foo', 'Bar' ),
			),
			array(
				'error'         => 1,
				'propertyCount' => 0,
			)
		);

		// #4 One valid DataValue + an error object
		$provider[] = array(
			array(
				DataValueFactory::getInstance()->newPropertyValue( 'Foo', 'Bar' ),
				DataValueFactory::getInstance()->newPropertyValue( '-Foo', 'bar' ),
			),
			array(
				'error'         => 1,
				'propertyCount' => 1,
				'propertyLabels' => array( 'Foo' ),
				'propertyValues' => array( 'Bar' )
			)
		);


		// #5 Error (Predefined)
		$provider[] = array(
			array(
				DataValueFactory::getInstance()->newPropertyValue( '_Foo', 'Bar' ),
			),
			array(
				'error'         => 1,
				'propertyCount' => 0,
			)
		);

		// #6 Error (Known predefined property)
		$provider[] = array(
			array(
				DataValueFactory::getInstance()->newPropertyValue( 'Modification date', 'Bar' ),
			),
			array(
				'error'         => 1,
				'propertyCount' => 0,
			)
		);

		return $provider;
	}

}
