<?php

namespace SMW\Test\SQLStore;

use SMW\SQLStore\TableDefinition;
use SMW\StoreFactory;

use SMWDataItem;

/**
 * Test for the TableDefinition class
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * @covers \SMW\SQLStore\TableDefinition
 *
 *
 * @group SMW
 * @group SMWExtension
 */
class TableDefinitionTest extends \SMW\Test\SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SQLStore\TableDefinition';
	}

	/**
	 * Helper method that returns a TableDefinition object
	 *
	 * @since 1.9
	 *
	 * @return TableDefinition
	 */
	private function newInstance( $DIType = '' , $tableName = '' ) {
		return new TableDefinition( $DIType, $tableName );
	}

	/**
	 * @test TableDefinition::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$this->assertInstanceOf( $this->getClass(), $this->newInstance() );
	}

	/**
	 * @test TableDefinition::getFields
	 * @test TableDefinition::getDiType
	 * @test TableDefinition::getName
	 *
	 * @since 1.9
	 */
	public function testGetters() {

		$diType = SMWDataItem::TYPE_NUMBER;
		$name   = 'smw_di_number';

		$instance = $this->newInstance( $diType, $name );

		$this->assertInternalType(
			'array',
			$instance->getFields( StoreFactory::getStore( 'SMWSQLStore3' ) ),
			'Asserts that getFields() returns an array'
		);

		$this->assertEquals(
			$diType,
			$instance->getDiType(),
			'Asserts that getDiType() returns the corret object'
		);

		$this->assertEquals(
			$name,
			$instance->getName(),
			'Asserts that getName() returns the corret object'
		);

	}

	/**
	 * @test TableDefinition::usesIdSubject
	 * @test TableDefinition::setUsesIdSubject
	 *
	 * @since 1.9
	 */
	public function testIdSubject() {

		$instance = $this->newInstance();
		$instance->setUsesIdSubject( false );

		$this->assertFalse(
			$instance->usesIdSubject(),
			'Asserts that usesIdSubject() returns false'
		);

	}

	/**
	 * @test TableDefinition::getFixedProperty
	 *
	 * @since 1.9
	 */
	public function testGetFixedProperty() {

		$this->setExpectedException( 'OutOfBoundsException' );
		$this->newInstance()->getFixedProperty();

	}

}
