<?php

namespace SMW\Tests\DataValues\ValueParsers;

use SMW\DataValues\ValueParsers\PropertyValueParser;

/**
 * @covers \SMW\DataValues\ValueParsers\PropertyValueParser
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyValueParserTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			PropertyValueParser::class,
			new PropertyValueParser()
		);
	}

	/**
	 * @dataProvider nameProvider
	 */
	public function testParse( $value, $invalidCharacterList, $expectedPropertyName, $expectedInverse ) {

		$instance = new PropertyValueParser();
		$instance->setInvalidCharacterList(
			$invalidCharacterList
		);

		list( $propertyName, $inverse ) = $instance->parse( $value );

		$this->assertSame(
			$expectedPropertyName,
			$propertyName
		);

		$this->assertSame(
			$expectedInverse,
			$inverse
		);
	}

	public function testEnforceUpperCase() {

		$instance = new PropertyValueParser();
		$instance->requireUpperCase( true );

		list( $propertyName, $inverse ) = $instance->parse( 'foo' );

		$this->assertSame(
			'Foo',
			$propertyName
		);
	}

	public function nameProvider() {

		$provider[] = array(
			'Foo',
			array(),
			'Foo',
			false
		);

		$provider[] = array(
			'-Foo',
			array(),
			'Foo',
			true
		);

		$provider[] = array(
			'<Foo>',
			array( '<', '>' ),
			null,
			null
		);

		$provider[] = array(
			'<Foo-<Bar>',
			array( '<', '>' ),
			null,
			null
		);

		$provider[] = array(
			'Foo-<Bar',
			array( '<', '>' ),
			'Foo-',
			false
		);

		$provider[] = array(
			'Foo#Bar',
			array(),
			null,
			null
		);

		$provider[] = array(
			'Foo|Bar',
			array( '|' ),
			null,
			null
		);

		$provider[] = array(
			'Foo.Bar',
			array(),
			null,
			null
		);

		$provider[] = array(
			'Foo[Bar',
			array( '[', ']' ),
			null,
			null
		);

		$provider[] = array(
			'Foo]Bar',
			array( '[', ']' ),
			null,
			null
		);

		return $provider;
	}

}
