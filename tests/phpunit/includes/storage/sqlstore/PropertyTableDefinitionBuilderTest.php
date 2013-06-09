<?php

namespace SMW\Test\SQLStore;

use SMW\SQLStore\PropertyTableDefinitionBuilder;

use SMWDataItem;

/**
 * Tests for the PropertyTableBuilder class
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 1.9
 *
 * @file
 *
 * @licence GNU GPL v2+
 * @author mwjames
 */

/**
 * @covers \SMW\SQLStore\PropertyTableDefinitionBuilder
 *
 * @ingroup SQLStoreTest
 *
 * @group SMW
 * @group SMWExtension
 */
class PropertyTableDefinitionBuilderTest extends \SMW\Test\SemanticMediaWikiTestCase {

	/**
	 * Returns the name of the class to be tested
	 *
	 * @return string|false
	 */
	public function getClass() {
		return '\SMW\SQLStore\PropertyTableDefinitionBuilder';
	}

	/**
	 * Helper method that returns a PropertyTableDefinitionBuilder object
	 *
	 * @since 1.9
	 *
	 * @param $dataItemDefinitions
	 * @param $specialProperties
	 * @param $fixedProperties
	 *
	 * @return PropertyTableDefinitionBuilder
	 */
	private function getInstance(
		$dataItemDefinitions = array(),
		$specialProperties = array(),
		$fixedProperties = array()
	) {
		return new PropertyTableDefinitionBuilder(
			$dataItemDefinitions,
			$specialProperties,
			$fixedProperties
		);
	}

	/**
	 * @test PropertyTableDefinitionBuilder::__construct
	 *
	 * @since 1.9
	 */
	public function testConstructor() {
		$instance = $this->getInstance();
		$this->assertInstanceOf( $this->getClass(), $instance );
	}

	/**
	 * @test PropertyTableDefinitionBuilder::doBuild
	 *
	 * @since 1.9
	 */
	public function testDITypes() {

		$test = array( SMWDataItem::TYPE_NUMBER => 'smw_di_number' );

		$instance = $this->getInstance( $test );
		$instance->doBuild();

		$definition = $instance->getDefinition( SMWDataItem::TYPE_NUMBER, 'smw_di_number' );
		$expected = array( 'smw_di_number' => $definition );

		$this->assertEquals( $expected, $instance->getTableDefinitions() );

	}

	/**
	 * @test PropertyTableDefinitionBuilder::doBuild
	 *
	 * @since 1.9
	 */
	public function testFixedProperties() {

		$propertyKey = $this->getRandomString();
		$test = array( $propertyKey => SMWDataItem::TYPE_NUMBER );

		$instance = $this->getInstance( array(), array(), $test );
		$instance->doBuild();

		$tableName = $instance->getTablePrefix() . '_' . md5( $propertyKey );
		$definition = $instance->getDefinition( SMWDataItem::TYPE_NUMBER, $tableName, $propertyKey );
		$expected = array(
			'definition' => array( $tableName => $definition ),
			'tableId' => array( $propertyKey => $tableName, '_SKEY' => null )
		);

		$this->assertEquals( $expected['definition'], $instance->getTableDefinitions() );
		$this->assertEquals( $expected['tableId'], $instance->getTableIds() );

	}

	/**
	 * @test PropertyTableDefinitionBuilder::doBuild
	 *
	 * @since 1.9
	 */
	public function testSpecialProperties() {

		$propertyKey = '_MDAT';
		$test = array( $propertyKey );

		$instance = $this->getInstance( array(), $test, array() );
		$instance->doBuild();

		$tableName = $instance->getTablePrefix() . strtolower( $propertyKey );
		$definition = $instance->getDefinition( SMWDataItem::TYPE_TIME, $tableName, $propertyKey );
		$expected = array( $tableName => $definition );

		$this->assertEquals( $expected, $instance->getTableDefinitions() );

	}

	/**
	 * @test PropertyTableDefinitionBuilder::doBuild (redirect)
	 *
	 * @since 1.9
	 */
	public function testRedirects() {

		$propertyKey = '_REDI';
		$test = array( $propertyKey );

		$instance = $this->getInstance( array(), $test, array() );
		$instance->doBuild();

		$tableName = $instance->getTablePrefix() . strtolower( $propertyKey );
		$definition = $instance->getDefinition( SMWDataItem::TYPE_WIKIPAGE, $tableName, $propertyKey );
		$expected = array( $tableName => $definition );
		$tableDefinitions = $instance->getTableDefinitions();

		$this->assertFalse( $tableDefinitions[$tableName]->usesIdSubject() );

	}
}
