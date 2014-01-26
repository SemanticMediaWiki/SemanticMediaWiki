<?php

namespace SMW\Test;

use SMW\DataValueFactory;
use SMW\HashIdGenerator;
use SMW\DIProperty;
use SMW\Subobject;

use SMWDIBlob;
use Title;

/**
 * @covers \SMW\Subobject
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
class SubobjectTest extends ParserTestCase {

	/**
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\Subobject';
	}

	/**
	 * @since 1.9
	 *
	 * @return SMWDataValue
	 */
	private function newDataValue( $propertyName, $value ){
		return DataValueFactory::getInstance()->newPropertyValue( $propertyName, $value );
	}

	/**
	 * @since 1.9
	 *
	 * @return Subobject
	 */
	private function newInstance( Title $title = null, $id = '' ) {

		if ( $title === null ) {
			$title = $this->newTitle();
		}

		$instance = new Subobject( $title );

		if ( $id === '' && $id !== null ) {
			$id = $instance->generateId( new HashIdGenerator( $this->newRandomString(), '_' ) );
		}

		$instance->setSemanticData( $id );
		return $instance;
	}

	/**
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @since 1.9
	 */
	public function testSetSemanticDataInvalidArgumentException() {

		$this->setExpectedException( 'InvalidArgumentException' );

		$instance = new Subobject( $this->newTitle() );
		$instance->setSemanticData( '' );

	}

	/**
	 * @since 1.9
	 */
	public function testSetSemanticData() {

		$instance = $this->newInstance( $this->newTitle() );

		$instance->setSemanticData( 'Foo' );

		$this->assertInstanceOf(
			'\Title',
			$instance->getTitle()
		);

		$this->assertInstanceOf(
			'\SMWContainerSemanticData',
			$instance->getSemanticData()
		);

		$this->assertEquals(
			'Foo',
			$instance->getSemanticData()->getSubject()->getSubobjectname(),
			'Asserts that getSubobjectname() returns with an expected result'
		);

	}

	/**
	 * @dataProvider getDataProvider
	 *
	 * @note For an anonymous identifier we only use the first character
	 * as comparison
	 *
	 * @since 1.9
	 */
	public function testGetId( array $test, array $expected, array $info ) {

		$subobject = $this->newInstance( $this->newTitle(), $test['identifier'] );

		$id = $expected['identifier'] === '_' ? substr( $subobject->getId(), 0, 1 ) : $subobject->getId();
		$this->assertEquals( $expected['identifier'], $id, $info['msg'] );

	}

	/**
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetProperty( array $test ) {

		$subobject = $this->newInstance( $this->newTitle(), $test['identifier'] );
		$this->assertInstanceOf( '\SMW\DIProperty', $subobject->getProperty() );

	}

	/**
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 */
	public function testaddDataValue( array $test, array $expected, array $info ) {

		$subobject = $this->newInstance( $this->newTitle(), $test['identifier'] );

		foreach ( $test['properties'] as $property => $value ){
			$subobject->addDataValue(
				$this->newDataValue( $property, $value )
			);
		}

		$this->assertCount( $expected['errors'], $subobject->getErrors(), $info['msg'] );
		$this->assertInstanceOf( '\SMW\SemanticData', $subobject->getSemanticData(), $info['msg'] );

		$semanticDataValidator = new SemanticDataValidator;
		$semanticDataValidator->assertThatPropertiesAreSet( $expected, $subobject->getSemanticData() );

	}

	/**
	 * @dataProvider newDataValueProvider
	 *
	 * @since 1.9
	 */
	public function testDataValueExaminer( array $test, array $expected ) {

		$property  = $this->newMockBuilder()->newObject( 'DIProperty', array(
			'findPropertyTypeID' => $test['property']['typeId'],
			'getKey'             => $test['property']['key'],
			'getLabel'           => $test['property']['label'],
		) );

		$dataValue = $this->newMockBuilder()->newObject( 'DataValue', array(
			'DataValueType' => $test['dataValue']['type'],
			'getDataItem'   => $test['dataValue']['dataItem'],
			'getProperty'   => $property,
			'isValid'       => true,
		) );

		$subobject = $this->newInstance( $this->newTitle(), $this->newRandomString() );
		$subobject->addDataValue( $dataValue );

		$this->assertCount( $expected['errors'], $subobject->getErrors() );
		$this->assertInstanceOf( '\SMW\SemanticData', $subobject->getSemanticData() );

		$semanticDataValidator = new SemanticDataValidator;
		$semanticDataValidator->assertThatPropertiesAreSet( $expected, $subobject->getSemanticData() );

	}

	/**
	 * @since 1.9
	 *
	 * @throws InvalidSemanticDataException
	 */
	public function testAddDataValueStringException() {

		$this->setExpectedException( '\SMW\InvalidSemanticDataException' );

		$subobject = new Subobject( $this->newTitle() );
		$subobject->addDataValue( $this->newDataValue( 'Foo', 'Bar' ) );

	}

	/**
	 * @since 1.9
	 *
	 * @throws InvalidSemanticDataException
	 */
	public function testGetSemanticDataInvalidSemanticDataException() {

		$this->setExpectedException( '\SMW\InvalidSemanticDataException' );

		$subobject = new Subobject( $this->newTitle() );
		$subobject->getSemanticData();

	}

	/**
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 */
	public function testGenerateId( array $test, array $expected, array $info ) {

		$subobject = $this->newInstance( $this->newTitle() );
		$this->assertEquals(
			'_',
			substr( $subobject->generateId( new HashIdGenerator( $test['identifier'], '_' ) ), 0, 1 ),
			$info['msg']
		);

	}

	/**
	 * @dataProvider getDataProvider
	 *
	 * @since 1.9
	 */
	public function testGetContainer( array $test, array $expected, array $info  ) {

		$subobject = $this->newInstance( $this->newTitle(), $test['identifier'] );
		$this->assertInstanceOf( '\SMWDIContainer', $subobject->getContainer(), $info['msg'] );

	}

	/**
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
					'propertyCount'  => 1,
					'propertyLabels' => 'Foo',
					'propertyValues' => 'Bar',
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
					'propertyCount'  => 1,
					'propertyLabels' => 'FooBar',
					'propertyValues' => 'Bar Foo',
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
					'propertyCount'  => 1,
					'propertyLabels' => array( 9001 ),
					'propertyValues' => array( 1001 ),
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
					'propertyCount'  => 2,
					'propertyLabels' => array( 1001, 'Foo' ),
					'propertyValues' => array( 9001, 'Bar' ),
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
					'propertyCount'  => 0,
					'propertyLabels' => '',
					'propertyValues' => '',
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
					'propertyCount'  => 0,
					'propertyLabels' => '',
					'propertyValues' => '',
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
					'propertyCount'  => 1,
					'propertyLabels' => array( $diPropertyError->getLabel() ),
					'propertyValues' => 'Foo',
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
	public function newDataValueProvider() {

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
					'propertyCount'  => 1,
					'propertyLabels' => 'TextExample',
					'propertyValues' => '<a href="http://username@example.org/path">Example</a>',
				)
			),
		);
	}
}
