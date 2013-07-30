<?php

namespace SMW\Test;

use SMW\DataValueFactory;
use SMW\DIProperty;
use SMW\Subobject;

use SMWDIBlob;
use Title;

/**
 * Tests for the Subobject class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\Subobject
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class SubobjectTest extends ParserTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\Subobject';
	}

	/**
	 * Helper method that returns a DataValue object
	 *
	 * @since 1.9
	 *
	 * @param $propertyName
	 * @param $value
	 *
	 * @return SMWDataValue
	 */
	private function getDataValue( $propertyName, $value ){
		return DataValueFactory::newPropertyValue( $propertyName, $value );
	}

	/**
	 * Helper method that returns a Subobject object
	 *
	 * @since 1.9
	 *
	 * @param $title
	 * @param $id
	 *
	 * @return Subobject
	 */
	private function getInstance( Title $title, $id = '' ) {

		$instance = new Subobject( $title );

		if ( $id === '' && $id !== null ) {
			$id = $instance->getAnonymousIdentifier( $this->getRandomString() );
		}

		$instance->setSemanticData( $id );
		return $instance;
	}

	/**
	 * @test Subobject::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {

		$instance = $this->getInstance( $this->getTitle() );
		$this->assertInstanceOf( $this->getClass(), $instance );

	}

	/**
	 * @test Subobject::newFromId
	 *
	 * @since 1.9
	 */
	public function testNewFromId() {

		$id = 'Foo';
		$instance = Subobject::newFromId( $this->getTitle(), $id );

		$this->assertInstanceOf( $this->getClass(), $instance );
		$this->assertEquals( $id, $instance->getId() );

	}

	/**
	 * @test Subobject::setSemanticData
	 *
	 * @since 1.9
	 */
	public function testSetSemanticData() {

		$instance = $this->getInstance( $this->getTitle() );

		$this->assertInstanceOf(
			'\SMWContainerSemanticData',
			$instance->setSemanticData( $this->getRandomString() )
		);
		$this->assertEmpty( $instance->setSemanticData( '' ) );

	}

	/**
	 * @test Subobject::getId
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $test
	 * @param array $expected
	 * @param array $info
	 */
	public function testGetId( array $test, array $expected, array $info ) {

		$subobject = $this->getInstance( $this->getTitle(), $test['identifier'] );
		// For an anonymous identifier we only use the first character as comparison
		$id = $expected['identifier'] === '_' ? substr( $subobject->getId(), 0, 1 ) : $subobject->getId();

		$this->assertEquals( $expected['identifier'], $id, $info['msg'] );

	}

	/**
	 * @test Subobject::getProperty
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $test
	 */
	public function testGetProperty( array $test ) {

		$subobject = $this->getInstance( $this->getTitle(), $test['identifier'] );
		$this->assertInstanceOf( '\SMW\DIProperty', $subobject->getProperty() );

	}

	/**
	 * @test Subobject::addPropertyValue
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $test
	 * @param array $expected
	 * @param array $info
	 */
	public function testAddPropertyValue( array $test, array $expected, array $info ) {

		$subobject = $this->getInstance( $this->getTitle(), $test['identifier'] );

		foreach ( $test['properties'] as $property => $value ){
			$subobject->addPropertyValue(
				$this->getDataValue( $property, $value )
			);
		}

		$this->assertCount( $expected['errors'], $subobject->getErrors(), $info['msg'] );
		$this->assertInstanceOf( '\SMW\SemanticData', $subobject->getSemanticData(), $info['msg'] );
		$this->assertSemanticData( $subobject->getSemanticData(), $expected );

	}

	/**
	 * @test Subobject::addPropertyValue
	 * @dataProvider getDataValueProvider
	 *
	 * @since 1.9
	 *
	 * @param array $test
	 * @param array $expected
	 */
	public function testDataValueExaminer( array $test, array $expected ) {

		$property  = $this->newMockObject( array(
			'findPropertyTypeID' => $test['property']['typeId'],
			'getKey'             => $test['property']['key'],
			'getLabel'           => $test['property']['label'],
		) )->getMockDIProperty();

		$dataValue = $this->newMockObject( array(
			'DataValueType' => $test['dataValue']['type'],
			'getDataItem'   => $test['dataValue']['dataItem'],
			'getProperty'   => $property,
			'isValid'       => true,
		) )->getMockDataValue();

		$subobject = $this->getInstance( $this->getTitle(), $this->getRandomString() );
		$subobject->addPropertyValue( $dataValue );

		$this->assertCount( $expected['errors'], $subobject->getErrors() );
		$this->assertInstanceOf( '\SMW\SemanticData', $subobject->getSemanticData() );
		$this->assertSemanticData( $subobject->getSemanticData(), $expected );

	}

	/**
	 * @test Subobject::addPropertyValue
	 *
	 * @since 1.9
	 *
	 * @throws InvalidSemanticDataException
	 */
	public function testAddPropertyValueStringException() {

		$this->setExpectedException( '\SMW\InvalidSemanticDataException' );

		$subobject = new Subobject(  $this->getTitle() );
		$subobject->addPropertyValue( $this->getDataValue( 'Foo', 'Bar' ) );

	}

	/**
	 * @test Subobject::getAnonymousIdentifier
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $test
	 * @param array $expected
	 * @param array $info
	 */
	public function testGetAnonymousIdentifier( array $test, array $expected, array $info ) {

		$subobject = $this->getInstance( $this->getTitle() );
		$this->assertEquals(
			'_',
			substr( $subobject->getAnonymousIdentifier( $test['identifier'] ), 0, 1 ),
			$info['msg']
		);

	}

	/**
	 * @test Subobject::getContainer
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 *
	 * @param array $test
	 * @param array $expected
	 * @param array $info
	 */
	public function testGetContainer( array $test, array $expected, array $info  ) {

		$subobject = $this->getInstance( $this->getTitle(), $test['identifier'] );
		$this->assertInstanceOf( '\SMWDIContainer',	$subobject->getContainer(), $info['msg'] );

	}

	/**
	 * Provides sample data of combinations used in connection with a
	 * Subobject instance
	 *
	 * @return array
	 */
	public function getDataProvider() {
		$diPropertyError = new DIProperty( DIProperty::TYPE_ERROR );
		return array(

			// #0
			array(
				array(
					'identifier' => 'Bar',
					'properties' => array( 'Foo' => 'bar' )
				),
				array(
					'errors' => 0,
					'identifier' => 'Bar',
					'propertyCount' => 1,
					'propertyLabel' => 'Foo',
					'propertyValue' => 'Bar',
				),
				array( 'msg'  => 'Failed asserting conditions for a named identifier' )
			),

			// #1
			array(
				array(
					'identifier' => '',
					'properties' => array( 'FooBar' => 'bar Foo' )
				),
				array(
					'errors' => 0,
					'identifier' => '_',
					'propertyCount' => 1,
					'propertyLabel' => 'FooBar',
					'propertyValue' => 'Bar Foo',
				),
				array( 'msg'  => 'Failed asserting conditions for an anon identifier' )
			),

			// #2
			array(
				array(
					'identifier' => 'foo',
					'properties' => array( 9001 => 1001 )
				),
				array(
					'errors' => 0,
					'identifier' => 'foo',
					'propertyCount' => 1,
					'propertyLabel' => array( 9001 ),
					'propertyValue' => array( 1001 ),
				),
				array( 'msg'  => 'Failed asserting conditions' )
			),

			// #3
			array(
				array(
					'identifier' => 'foo bar',
					'properties' => array( 1001 => 9001, 'Foo' => 'Bar' )
				),
				array(
					'errors' => 0,
					'identifier' => 'foo bar',
					'propertyCount' => 2,
					'propertyLabel' => array( 1001, 'Foo' ),
					'propertyValue' => array( 9001, 'Bar' ),
				),
				array( 'msg'  => 'Failed asserting conditions' )
			),

			// #4
			array(
				array(
					'identifier' => 'bar',
					'properties' => array( '_FooBar' => 'bar Foo' )
				),
				array(
					'errors' => 1,
					'identifier' => 'bar',
					'propertyCount' => 0,
					'propertyLabel' => '',
					'propertyValue' => '',
				),
				array( 'msg'  => 'Failed asserting that a property with a leading underscore would produce an error' )
			),

			// #5
			array(
				array(
					'identifier' => 'bar',
					'properties' => array( '-FooBar' => 'bar Foo' )
				),
				array(
					'errors' => 1,
					'identifier' => 'bar',
					'propertyCount' => 0,
					'propertyLabel' => '',
					'propertyValue' => '',
				),
				array( 'msg'  => 'Failed asserting that an inverse property would produce an error' )
			),

			// #6
			array(
				array(
					'identifier' => 'bar',
					'properties' => array( 'Foo' => '' )
				),
				array(
					'identifier' => 'bar',
					'errors' => 1,
					'propertyCount' => 1,
					'propertyLabel' => array( $diPropertyError->getLabel() ),
					'propertyValue' => 'Foo',
				),
				array( 'msg'  => 'Failed asserting that an improper value for a _wpg property would add "Has improper value for"' )
			)
		);
	}

	/**
	 * Provides sample data for various dataItem/datValues
	 *
	 * @return array
	 */
	public function getDataValueProvider() {

		return array(

			// #0 Bug 49530
			array(
				array(
					'property' => array(
						'DI' => 'SMWDIProperty',
						'typeId' => '_txt',
						'label'  => 'TextExample',
						'key'    => 'TextExample'
					),
					'dataValue' => array(
						'type' => 'SMWStringValue',
						'dataItem' => new SMWDIBlob( '<a href="http://username@example.org/path">Example</a>' )
					)
				),
				array(
					'errors' => 0,
					'propertyCount' => 1,
					'propertyLabel' => 'TextExample',
					'propertyValue' => '<a href="http://username@example.org/path">Example</a>',
				)
			),
		);
	}
}
