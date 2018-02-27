<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\FormatListWidget;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\FormatListWidget
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class FormatListWidgetTest extends \PHPUnit_Framework_TestCase {

	public function testEmptyParameters() {

		$stringValidator = TestEnvironment::newValidatorFactory()->newStringValidator();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$stringValidator->assertThatStringContains(
			[
				'<span class="smw-ask-format-list"><input type="hidden" value="yes" name="eq"',
				'<option value="broadtable" selected="">.*</option>'
			],
			FormatListWidget::selectList( $title, [] )
		);
	}

	public function testSetResultFormats() {

		$stringValidator = TestEnvironment::newValidatorFactory()->newStringValidator();

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		FormatListWidget::setResultFormats(
			[
				'rdf' => 'SomeClassReference'
			]
		);

		$stringValidator->assertThatStringContains(
			[
				'<option value="broadtable">.*</option>',
				'<option data-isexport="1" value="rdf" selected="">.*</option>'
			],
			FormatListWidget::selectList( $title, [ 'format' => 'rdf' ] )
		);
	}

}
