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

		$fieldTypes = array(
			'double' => 'DOUBLE'
		);

		$provider[] = array(
			FieldType::TYPE_DOUBLE,
			$fieldTypes,
			'DOUBLE'
		);

		$provider[] = array(
			array( FieldType::TYPE_DOUBLE ),
			$fieldTypes,
			'DOUBLE'
		);

		$provider[] = array(
			array( FieldType::TYPE_DOUBLE, 'NOT NULL' ),
			$fieldTypes,
			'DOUBLE NOT NULL'
		);

		$provider[] = array(
			array( FieldType::TYPE_DOUBLE, 'NOT NULL', 'thirdParameterIsNeglected' ),
			$fieldTypes,
			'DOUBLE NOT NULL'
		);

		$provider[] = array(
			array( 'notMatchableTypeThereforeReturnAsIs' ),
			$fieldTypes,
			'notMatchableTypeThereforeReturnAsIs'
		);

		return $provider;
	}

}
