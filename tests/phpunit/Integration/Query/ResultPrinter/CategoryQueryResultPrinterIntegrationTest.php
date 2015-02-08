<?php

namespace SMW\Tests\Integration\Query\ResultPrinter;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Utils\UtilityFactory;

use Title;

/**
 * @group semantic-mediawiki-integration
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class CategoryQueryResultPrinterIntegrationTest extends MwDBaseUnitTestCase {

	private $subjects = array();
	private $pageCreator;

	private $stringBuilder;
	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();

		$this->pageCreator = $utilityFactory->newPageCreator();
		$this->stringBuilder = $utilityFactory->newStringBuilder();
		$this->stringValidator = $utilityFactory->newValidatorFactory()->newStringValidator();
	}

	protected function tearDown() {

		$pageDeleter = UtilityFactory::getInstance()->newPageDeleter();

		$pageDeleter
			->doDeletePoolOfPages( $this->subjects );

		parent::tearDown();
	}

	/**
	 * @query {{#ask: [[Modification date::+]] [[Category:...]] |format=category }}
	 */
	public function testLimitedSortedCategoryFormatForFilteredValue() {

		foreach ( array( 'Foo', 'Bar', 'テスト' ) as $title ) {

			$this->pageCreator
				->createPage( Title::newFromText( $title ) )
				->doEdit( '[[Category:LimitedSortedFormatCategoryForFilteredValue]]');

			$this->subjects[] = $this->pageCreator->getPage();
		}

		$this->stringBuilder
			->addString( '{{#ask:' )
			->addString( '[[Modification date::+]][[Category:LimitedSortedFormatCategoryForFilteredValue]]' )
			->addString( '|?Modification date' )
			->addString( '|format=category' )
			->addString( '|limit=10' )
			->addString( '|sort=Modification date' )
			->addString( '|order=desc' )
			->addString( '}}' );

		$this->pageCreator
			->createPage( Title::newFromText( __METHOD__ ) )
			->doEdit( $this->stringBuilder->getString() );

		$this->subjects[] = $this->pageCreator->getPage();

		$parserOutput = $this->pageCreator->getEditInfo()->output;

		$expected = array(
			'<div style="float: left; width: 33%;">',
			'id=".E3.83.86">テ</span>',
			'title="テスト">テスト</a>',
			'<div style="float: left; width: 33%;">',
			'id="B">B</span>',
			'title="Bar">Bar</a>',
			'<div style="float: left; width: 33%;">',
			'id="F">F</span>',
			'title="Foo">Foo</a>',
			'<br style="clear: both;" />'
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$parserOutput->getText()
		);
	}

}
