<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\FormatSelectionWidget;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\FormatSelectionWidget
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FormatSelectionWidgetTest extends \PHPUnit_Framework_TestCase {

	public function testEmptyParameters() {

		$stringValidator = TestEnvironment::newValidatorFactory()->newStringValidator();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$stringValidator->assertThatStringContains(
			[
				'<fieldset id="format" class="smw-ask-format" style="margin-top:0px;"><legend>.*</legend>',
				'<span class="smw-ask-format-list"><input type="hidden" value="yes" name="eq"',
				'<option value="broadtable" selected="">.*</option>'
			],
			FormatSelectionWidget::selection( $title, [] )
		);
	}

	public function testSetResultFormats() {

		$stringValidator = TestEnvironment::newValidatorFactory()->newStringValidator();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		FormatSelectionWidget::setResultFormats(
			[
				'rdf' => 'SomeClassReference'
			]
		);

		$stringValidator->assertThatStringContains(
			[
				'<option value="broadtable">.*</option>',
				'<option data-isexport="1" value="rdf" selected="">.*</option>'
			],
			FormatSelectionWidget::selection( $title, [ 'format' => 'rdf' ] )
		);
	}

}
