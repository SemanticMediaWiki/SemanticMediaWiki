<?php

namespace SMW\Tests;

use SMW\TypesRegistry;

/**
 * @covers \SMW\TypesRegistry
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class TypesRegistryTest extends \PHPUnit_Framework_TestCase {

	public function testGetDataTypeList() {

		$this->assertInternalType(
			'array',
			TypesRegistry::getDataTypeList()
		);
	}

	public function testGetPropertyList() {

		$this->assertInternalType(
			'array',
			TypesRegistry::getPropertyList()
		);
	}

	public function testGetFixedProperties_IDList() {

		$propertyList = TypesRegistry::getPropertyList();

		foreach ( TypesRegistry::getFixedProperties( 'id' ) as $key => $id ) {
			$this->assertArrayHasKey( $key, $propertyList );
		}
	}

	public function testGetFixedProperties_DefaultFixed() {

		$propertyList = TypesRegistry::getPropertyList();

		foreach ( TypesRegistry::getFixedProperties( 'default_fixed' ) as $key ) {
			$this->assertArrayHasKey( $key, $propertyList );
		}
	}

	public function testGetFixedProperties_CustomFixed() {

		$propertyList = TypesRegistry::getPropertyList();

		foreach ( TypesRegistry::getFixedProperties( 'custom_fixed' ) as $key ) {
			$this->assertArrayHasKey( $key, $propertyList );
		}
	}

	public function testGetFixedProperties_CompareFixed() {

		$customList = TypesRegistry::getFixedProperties( 'custom_fixed' );

		// Both list are exclusive (members of one list should not appear in the
		// other list)
		foreach ( TypesRegistry::getFixedProperties( 'default_fixed' ) as $key ) {
			$this->assertFalse( array_search( $key, $customList ), $key );
		}
	}

	/**
	 * @dataProvider typeList
	 */
	public function testTypeList_FirstCharUnderscore( $key, $def ) {
		$this->assertTrue( $key{0} === '_' );
	}

	/**
	 * @dataProvider typeList
	 */
	public function testTypeList_ClassExists( $key, $def ) {
		$this->assertTrue( class_exists( $def[0] ) );
	}

	/**
	 * @dataProvider propertyList
	 */
	public function testPropertyList_FirstCharUnderscore( $key, $def ) {
		$this->assertTrue( $key{0} === '_' );
	}

	public function typeList() {

		$excludes = [];

		// Requires Maps/Semantic Maps hence remove from the
		// test list
		$excludes = [ '_geo', '_gpo' ];

		foreach ( TypesRegistry::getDataTypeList() as $key => $def ) {

			if ( in_array( $key, $excludes ) ) {
				continue;
			}

			yield[ $key, $def ];
		}
	}

	public function propertyList() {

		$excludes = [];

		foreach ( TypesRegistry::getPropertyList() as $key => $def ) {

			if ( in_array( $key, $excludes ) ) {
				continue;
			}

			yield[ $key, $def ];
		}
	}

}
