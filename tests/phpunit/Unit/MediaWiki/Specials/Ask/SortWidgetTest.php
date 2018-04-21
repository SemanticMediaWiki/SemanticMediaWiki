<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\SortWidget;
use SMW\Tests\TestEnvironment;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\SortWidget
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class SortWidgetTest extends \PHPUnit_Framework_TestCase {

	private $stringValidator;

	protected function setUp() {
		$testEnvironment = new TestEnvironment();

		$this->stringValidator = $testEnvironment->getUtilityFactory()->newValidatorFactory()->newStringValidator();
	}

	public function testDisabled() {

		SortWidget::setSortingSupport( false );

		$this->assertEmpty(
			SortWidget::sortSection( [] )
		);
	}

	public function testEnabledWithEmptyParameters() {

		SortWidget::setSortingSupport( true );

		$this->assertContains(
			'<div id="options-sort" class="smw-ask-options-sort">',
			SortWidget::sortSection( [] )
		);
	}

	public function testEnabledWithParameters() {

		SortWidget::setSortingSupport( true );
		SortWidget::setRandSortingSupport( true );

		$result = SortWidget::sortSection(
			[
				'sort' => 'Foo,bar',
				'order' => 'asc,DESC'
			]
		);

		$this->stringValidator->assertThatStringContains(
			[
				'<div id="sort_div_0" class="smw-ask-sort-input">',
				'<input name="sort_num[]" size="35" class="smw-property-input autocomplete-arrow" value="Foo"',
				'<select name="order_num[]"><option selected="selected" value="asc">'
			],
			$result
		);

		$this->stringValidator->assertThatStringContains(
			[
				'<div id="sort_div_1" class="smw-ask-sort-input">',
				'<input name="sort_num[]" size="35" class="smw-property-input autocomplete-arrow" value="bar"',
				'<select name="order_num[]"><option value="asc">.*</option><option selected="selected" value="desc">'
			],
			$result
		);
	}

}
