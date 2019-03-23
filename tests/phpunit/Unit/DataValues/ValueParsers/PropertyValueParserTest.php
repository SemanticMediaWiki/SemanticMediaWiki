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

		list( $propertyName, $capitalizedName, $inverse ) = $instance->parse( $value );

		$this->assertSame(
			$expectedPropertyName,
			$propertyName
		);

		$this->assertSame(
			$expectedInverse,
			$inverse
		);
	}

	public function testEnforceFirstCharUpperCase() {

		$instance = new PropertyValueParser();
		$instance->isCapitalLinks( false );
		$instance->reqCapitalizedFirstChar( true );

		list( $propertyName, $capitalizedName, $inverse ) = $instance->parse( 'foo' );

		$this->assertSame(
			'foo',
			$propertyName
		);

		$this->assertSame(
			'Foo',
			$capitalizedName
		);
	}

	public function nameProvider() {

		$provider[] = [
			'Foo',
			[],
			'Foo',
			false
		];

		$provider[] = [
			'Fo o',
			[],
			'Fo o',
			false
		];

		$provider[] = [
			'Fo o  bar',
			[],
			'Fo o bar',
			false
		];

		$provider[] = [
			'Fo_o__bar',
			[],
			'Fo o bar',
			false
		];

		// User property
		$provider[] = [
			'Fo_o____bar',
			[],
			'Fo o bar',
			false
		];

		// Predefined property
		$provider[] = [
			'_Fo_o____bar',
			[],
			'_Fo_o____bar',
			false
		];

		$provider[] = [
			'[ Foo',
			[],
			'Foo',
			false
		];

		$provider[] = [
			'[Foo',
			[],
			'Foo',
			false
		];

		$provider[] = [
			'Foo ]',
			[],
			'Foo',
			false
		];

		$provider[] = [
			'Foo]',
			[],
			'Foo',
			false
		];

		$provider[] = [
			'-Foo',
			[],
			'Foo',
			true
		];

		$provider[] = [
			'<Foo>',
			[ '<', '>' ],
			null,
			null
		];

		$provider[] = [
			'<Foo-<Bar>',
			[ '<', '>' ],
			null,
			null
		];

		$provider[] = [
			'Foo-<Bar',
			[ '<', '>' ],
			'Foo-',
			false
		];

		$provider[] = [
			'Foo#Bar',
			[],
			null,
			null
		];

		$provider[] = [
			'Foo|Bar',
			[ '|' ],
			null,
			null
		];

		$provider[] = [
			'Foo.Bar',
			[],
			null,
			null
		];

		$provider[] = [
			'Foo[Bar',
			[ '[', ']' ],
			null,
			null
		];

		$provider[] = [
			'Foo]Bar',
			[ '[', ']' ],
			null,
			null
		];

		return $provider;
	}

}
