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
			$expected['description'],
			$description->getQueryString()
		);
	}

	/**
	 * @dataProvider valueProvider
	 */
	public function testGetWikiValue( $properties, $value, $expected ) {

		$instance = new RecordValue( '_rec' );
		$instance->setFieldProperties( $properties );

		$instance->setUserValue( $value );

		$this->assertEquals(
			$expected['wikivalue'],
			$instance->getWikiValue()
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
			array(
				'description' => "[[Foo::Title without special characters]] [[Bar::2001]]",
				'wikivalue'   => "Title without special characters; 2001"
			)

		);

		$provider[] = array(
			$properties,
			"Title with $&%'* special characters;(..&^%..)",
			array(
				'description' => "[[Foo::Title with $&%'* special characters]] [[Bar::(..&^%..)]]",
				'wikivalue'   => "Title with $&%'* special characters; (..&^%..)"
			)
		);

		$provider[] = array(
			$properties,
			" Title with space before ; After the divider ",
			array(
				'description' => "[[Foo::Title with space before]] [[Bar::After the divider]]",
				'wikivalue'   => "Title with space before; After the divider"
			)
		);

		$provider[] = array(
			$properties,
			" Title with backslash\; escape ; After the divider ",
			array(
				'description' => "[[Foo::Title with backslash; escape]] [[Bar::After the divider]]",
				'wikivalue'   => "Title with backslash\; escape; After the divider"
			)
		);

		return $provider;
	}

}
