<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\ParameterInput;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\ParameterInput
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class ParameterInputTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$paramDefinition = $this->getMockBuilder( '\ParamProcessor\ParamDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			ParameterInput::class,
			new ParameterInput( $paramDefinition, '' )
		);
	}

	/**
	 * @dataProvider listValueProvider
	 */
	public function testGetHtmlOnCheckboxList( $currentValue, $allowedValues, $expected ) {

		$stringValidator = TestEnvironment::newValidatorFactory()->newStringValidator();

		$paramDefinition = $this->getMockBuilder( '\ParamProcessor\ParamDefinition' )
			->disableOriginalConstructor()
			->getMock();

		$paramDefinition->expects( $this->atLeastOnce() )
			->method( 'getAllowedValues' )
			->will( $this->returnValue( $allowedValues ) );

		$paramDefinition->expects( $this->any() )
			->method( 'isList' )
			->will( $this->returnValue( true ) );

		$instance = new ParameterInput(
			$paramDefinition,
			$currentValue
		);

		$stringValidator->assertThatStringContains(
			$expected,
			$instance->getHtml()
		);
	}

	public function listValueProvider() {

		$provider[] = [
			'Foo',
			[ 'Foo', 'Bar' ],
			[
				'<span class="parameter-checkbox-input" style="white-space: nowrap; padding-right: 5px;"><input type="checkbox" name="[]" value="Foo" checked="".*><tt>Foo</tt></span>',
				'<span class="parameter-checkbox-input" style="white-space: nowrap; padding-right: 5px;"><input type="checkbox" name="[]" value="Bar".*><tt>Bar</tt></span>'
			],

		];

		$provider[] = [
			[ 'Foo' ],
			[ 'Foo', 'Bar' ],
			[
				'<span class="parameter-checkbox-input" style="white-space: nowrap; padding-right: 5px;"><input type="checkbox" name="[]" value="Foo" checked="".*><tt>Foo</tt></span>',
				'<span class="parameter-checkbox-input" style="white-space: nowrap; padding-right: 5px;"><input type="checkbox" name="[]" value="Bar".*><tt>Bar</tt></span>'
			],

		];

		$provider[] = [
			[ 'Foo, Bar' ],
			[ 'Foo', 'Bar' ],
			[
				'<span class="parameter-checkbox-input" style="white-space: nowrap; padding-right: 5px;"><input type="checkbox" name="[]" value="Foo" checked="".*><tt>Foo</tt></span>',
				'<span class="parameter-checkbox-input" style="white-space: nowrap; padding-right: 5px;"><input type="checkbox" name="[]" value="Bar" checked="".*><tt>Bar</tt></span>'
			],

		];

		$provider[] = [
			[ 'Foo,foo bar' ],
			[ 'Foo', 'foo bar' ],
			[
				'<span class="parameter-checkbox-input" style="white-space: nowrap; padding-right: 5px;"><input type="checkbox" name="[]" value="Foo" checked="".*><tt>Foo</tt></span>',
				'<span class="parameter-checkbox-input" style="white-space: nowrap; padding-right: 5px;"><input type="checkbox" name="[]" value="foo bar" checked="".*><tt>foo bar</tt></span>'
			],

		];

		return $provider;
	}

}
