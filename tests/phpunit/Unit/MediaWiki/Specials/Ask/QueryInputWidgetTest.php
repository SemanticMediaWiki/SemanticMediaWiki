<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\QueryInputWidget;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\QueryInputWidget
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class QueryInputWidgetTest extends \PHPUnit_Framework_TestCase {

	private $stringValidator;

	protected function setUp() {
		$testEnvironment = new TestEnvironment();

		$this->stringValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newStringValidator();
	}

	public function testInput() {

		$this->stringValidator->assertThatStringContains(
			[
				'<div id="query" class="smw-ask-query" style="margin-bottom:10px; margin-top:10px;">',
				'<div style="width: 100%;" class="smw-table">',
				'<div class="smw-table-header"><div class="smw-table-cell">.*</div><div class="smw-table-cell"></div><div class="smw-table-cell">.*</div></div>',
				'<div class="smw-table-row"><div class="smw-table-cell"><textarea class="smw-ask-query-condition" name="q" rows="6">Foo</textarea></div>',
				'<div class="smw-table-cell"><textarea id="smw-property-input" class="smw-ask-query-printout" name="po" rows="6">Bar</textarea>'
			],
			QueryInputWidget::input( 'Foo', 'Bar' )
		);
	}

}
