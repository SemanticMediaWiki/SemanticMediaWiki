<?php

namespace SMW\Tests\SQLStore\EntityStore;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\SQLStore\EntityStore\FieldList;
use SMW\Tests\PHPUnitCompat;

/**
 * @covers \SMW\SQLStore\EntityStore\FieldList
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.2
 *
 * @author mwjames
 */
class FieldListTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			FieldList::class,
			new FieldList( [] )
		);
	}

	public function testGetCountListByType() {

		$countMaps = [
			'_hash_0987654321' => [
				42 => [
					'Foo' => 1001
				],
				9000 => [
					'_INST' => [
						'x1' => true,
						'x2' => true
					]
				]
			]
		];

		$instance = new FieldList( $countMaps );

		$this->assertEquals(
			['Foo' => 1001 ],
			$instance->getCountListByType( FieldList::PROPERTY_LIST )
		);

		$this->assertEquals(
			[
				'x1' => 1,
				'x2' => 1
			],
			$instance->getCountListByType( FieldList::CATEGORY_LIST )
		);
	}

	public function testGetHashList() {

		$countMaps = [
			'_hash_0987654321' => [
				42 => [
					'Foo' => 1001
				],
				9000 => [
					'_INST' => [
						'x1' => true,
						'x2' => true
					]
				]
			]
		];

		$instance = new FieldList( $countMaps );

		$this->assertEquals(
			[

				'Foo' => '909d8ab26ea49adb7e1b106bc47602050d07d19f',
				'x1'  => '06324e92a45943ee119c12580077eb7d0c14226b',
				'x2'  => '8d86f35741aaf8649d5601cef12e1d95ed19846a'
			],
			$instance->getHashList()
		);
	}

}
