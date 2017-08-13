<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\ParametersFormWidget;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\ParametersFormWidget
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ParametersFormWidgetTest extends \PHPUnit_Framework_TestCase {

	private $stringValidator;

	protected function setUp() {
		$testEnvironment = new TestEnvironment();

		$this->stringValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newStringValidator();
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testCreateParametersForm( $format, $parameters, $expected ) {

		$this->stringValidator->assertThatStringContains(
			$expected,
			ParametersFormWidget::parameterList( $format, $parameters )
		);
	}

	public function parametersProvider() {

		$provider[] = array(
			'',
			array(),
			'<table class="smw-ask-options-list" width="100%"><tbody><tr class="smw-ask-options-row-odd"></tr></tbody></table>'
		);

		$provider[] = array(
			'table',
			array(),
			[
				'<table class="smw-ask-options-list"',
				'<input size="6" style="width: 95%;" value="50" name="p[limit]"',
				'<input size="6" style="width: 95%;" value="0" name="p[offset]"'
			]
		);

		$provider[] = array(
			'table',
			[
				'limit'  => 9999,
				'offset' => 42
			],
			[
				'<input size="6" style="width: 95%;" value="9999" name="p[limit]"',
				'<input size="6" style="width: 95%;" value="42" name="p[offset]"'
			]
		);

		return $provider;
	}

}
