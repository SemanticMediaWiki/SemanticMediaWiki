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

	private $stringValidator;

	protected function setUp() {
		$testEnvironment = new TestEnvironment();

		$this->stringValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newStringValidator();
	}

	public function testEmptyParameters() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->stringValidator->assertThatStringContains(
			[
				'<fieldset id="format" class="smw-ask-format" style="margin-top:0px;"><legend>.*</legend>',
				'<span class="smw-ask-format-list"><input type="hidden" value="yes" name="eq"',
				'<option value="broadtable" selected="selected">.*</option>'
			],
			FormatSelectionWidget::selection( $title, [] )
		);
	}

	public function testSetResultFormats() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		FormatSelectionWidget::setResultFormats(
			[
				'rdf' => 'SomeClassReference'
			]
		);

		$this->stringValidator->assertThatStringContains(
			[
				'<option value="broadtable">.*</option>',
				'<option value="rdf" selected="selected">.*</option>'
			],
			FormatSelectionWidget::selection( $title, [ 'format' => 'rdf' ] )
		);
	}

}
