<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\ParametersWidget;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\ParametersWidget
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ParametersWidgetTest extends \PHPUnit_Framework_TestCase {

	private $stringValidator;

	protected function setUp() {
		$testEnvironment = new TestEnvironment();

		$this->stringValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newStringValidator();
	}

	public function testFieldset() {

		$parameters = [];

		$this->stringValidator->assertThatStringContains(
			[
				'<fieldset><legend>.*</legend><span class="smw-ask-format-list">',
				'<input type="checkbox" id="options-toggle"/><div id="options-list" class="options-list"><div class="options-parameter-list">'
			],
			ParametersWidget::fieldset( \Title::newFromText( 'Foo' ), $parameters )
		);
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testCreateParametersForm( $format, $parameters, $expected ) {

		$parameters['format'] = $format;

		$this->stringValidator->assertThatStringContains(
			$expected,
			ParametersWidget::parameterList( $parameters )
		);
	}

	public function parametersProvider() {

		$provider[] = [
			'',
			[],
			'<div class="smw-table smw-ask-options-list" width="100%"><div class="smw-table-row smw-ask-options-row-odd"></div></div>'
		];

		$provider[] = [
			'table',
			[],
			[
				'<div class="smw-table smw-ask-options-list" width="100%"',
				'<input class="parameter-number-input" size="6" style="width: 95%;" value="50" name="p[limit]"',
				'<input class="parameter-number-input" size="6" style="width: 95%;" value="0" name="p[offset]"'
			]
		];

		$provider[] = [
			'table',
			[
				'limit'  => 9999,
				'offset' => 42
			],
			[
				'<input class="parameter-number-input" size="6" style="width: 95%;" value="9999" name="p[limit]"',
				'<input class="parameter-number-input" size="6" style="width: 95%;" value="42" name="p[offset]"'
			]
		];

		return $provider;
	}

}
