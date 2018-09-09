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

	public function testInput() {

		$stringValidator = TestEnvironment::newValidatorFactory()->newStringValidator();

		$stringValidator->assertThatStringContains(
			[
				'<div class="smw-table" style="width: 100%;"><div class="smw-table-row">',
				'<div class="smw-table-cell smw-ask-condition slowfade"><fieldset><legend>.*</legend><textarea id="ask-query-condition" class="smw-ask-query-condition" name="q" rows="6" placeholder="...">Foo</textarea>',
				'<div class="smw-table-cell smw-ask-printhead slowfade"><fieldset><legend>.*</legend><textarea id="smw-property-input" class="smw-ask-query-printout" name="po" rows="6" placeholder="...">Bar</textarea>'
			],
			QueryInputWidget::table( 'Foo', 'Bar' )
		);
	}

}
