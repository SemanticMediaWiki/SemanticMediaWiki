<?php

namespace SMW\Tests;

use SMW\DefaultList;

/**
 * @covers \SMW\DefaultList
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class DefaultListTest extends \PHPUnit_Framework_TestCase {

	public function testGetTypeList() {

		$this->assertInternalType(
			'array',
			DefaultList::getTypeList()
		);
	}

	/**
	 * @dataProvider typeList
	 */
	public function testTypeList_FirstCharUnderscore( $key, $def ) {
		$this->assertTrue(
			$key{0} === '_'
		);
	}

	/**
	 * @dataProvider typeList
	 */
	public function testTypeList_ClassExists( $key, $def ) {
		$this->assertTrue(
			class_exists( $def[0] )
		);
	}

	public function testGetPropertyList() {

		$this->assertInternalType(
			'array',
			DefaultList::getPropertyList()
		);
	}

	/**
	 * @dataProvider propertyList
	 */
	public function testPropertyList_FirstCharUnderscore( $key, $def ) {
		$this->assertTrue(
			$key{0} === '_'
		);
	}

	public function typeList() {

		$excludes = [];

		if ( !defined( 'SM_VERSION' ) ) {
			$excludes = [ '_geo', '_gpo' ];
		}

		foreach ( DefaultList::getTypeList() as $key => $def ) {

			if ( in_array( $key, $excludes ) ) {
				continue;
			}

			yield[ $key, $def ];
		}
	}

	public function propertyList() {

		$excludes = [];

		foreach ( DefaultList::getPropertyList() as $key => $def ) {

			if ( in_array( $key, $excludes ) ) {
				continue;
			}

			yield[ $key, $def ];
		}
	}

}
