<?php

namespace SMW\Test;

use SMW\DataValueFactory;
use SMW\SemanticData;

/**
 * Tests for the SemanticData class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\SemanticData
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
class SemanticDataTest extends SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SemanticData';
	}

	/**
	 * Helper method that returns a SemanticData object
	 *
	 * @since 1.9
	 *
	 * @return SemanticData
	 */
	private function newInstance() {
		return new SemanticData( $this->newSubject() );
	}

	/**
	 * @test SemanticData::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
		$this->assertInstanceOf( 'SMWSemanticData', $this->newInstance() );
	}

	/**
	 * @test SemanticData::addDataValue
	 * @dataProvider dataValueDataProvider
	 *
	 * @since 1.9
	 *
	 * @param $dataValues
	 * @param $expected
	 */
	public function testAddDataValue( $dataValues, $expected ) {

		$instance = $this->newInstance();

		foreach ( $dataValues as $dataValue ) {
			$instance->addDataValue( $dataValue );
		}

		if ( $expected['error'] === 0 ){
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
