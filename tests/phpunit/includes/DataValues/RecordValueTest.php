<?php

namespace SMW\Tests\DataValues;

use SMW\DIProperty;
use SMWRecordValue as RecordValue;

/**
 * @covers \SMWRecordValue
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class RecordValueTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMWRecordValue',
			new RecordValue( '_rec' )
		);
	}

	/**
	 * @dataProvider valueProvider
	 */
	public function testGetQueryDescription( $properties, $value, $expected ) {

		$instance = new RecordValue( '_rec' );
		$instance->setFieldProperties( $properties );

		$description = $instance->getQueryDescription( htmlspecialchars( $value ) );

		$this->assertEquals(
			$expected,
			$description->getQueryString()
		);
	}

	public function valueProvider() {

		$properties = array(
			new DIProperty( 'Foo'),
			new DIProperty( 'Bar' ),
			'InvalidFieldPropertyNotSet'
		);

		$provider[] = array(
			$properties,
			"Title without special characters;2001",
			"[[Foo::Title without special characters]] [[Bar::2001]]"
		);

		$provider[] = array(
			$properties,
			"Title with $&%'* special characters;(..&^%..)",
			"[[Foo::Title with $&%'* special characters]] [[Bar::(..&^%..)]]"
		);

		$provider[] = array(
			$properties,
			" Title with space before ; After the divider ",
			"[[Foo::Title with space before]] [[Bar::After the divider]]"
		);

		return $provider;
	}

}
