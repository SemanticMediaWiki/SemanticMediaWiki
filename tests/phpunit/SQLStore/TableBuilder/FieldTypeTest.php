<?php

namespace SMW\Tests\SQLStore\TableBuilder;

use SMW\SQLStore\TableBuilder\FieldType;

/**
 * @covers \SMW\SQLStore\TableBuilder\FieldType
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class FieldTypeTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider fieldTypeProvider
	 */
	public function testMapType( $fieldType, $fieldTypes, $expected ) {

		$this->assertEquals(
			$expected,
			FieldType::mapType( $fieldType, $fieldTypes )
		);
	}

	public function fieldTypeProvider() {

		$fieldTypes = [
			'double' => 'DOUBLE'
		];

		$provider[] = [
			FieldType::TYPE_DOUBLE,
			$fieldTypes,
			'DOUBLE'
		];

		$provider[] = [
			[ FieldType::TYPE_DOUBLE ],
			$fieldTypes,
			'DOUBLE'
		];

		$provider[] = [
			[ FieldType::TYPE_DOUBLE, 'NOT NULL' ],
			$fieldTypes,
			'DOUBLE NOT NULL'
		];

		$provider[] = [
			[ FieldType::TYPE_DOUBLE, 'NOT NULL', 'thirdParameterIsNeglected' ],
			$fieldTypes,
			'DOUBLE NOT NULL'
		];

		$provider[] = [
			[ 'notMatchableTypeThereforeReturnAsIs' ],
			$fieldTypes,
			'notMatchableTypeThereforeReturnAsIs'
		];

		$provider[] = [
			[ FieldType::TYPE_ENUM, [ 'a', 'b', 'c' ] ],
			[ 'enum' => 'ENUM' ],
			"ENUM('a','b','c')"
		];

		return $provider;
	}

}
