<?php

namespace SMW\Tests\Integration\Query\ResultPrinters;

use SMW\Tests\DatabaseTestCase;
use SMW\Tests\Utils\UtilityFactory;
use Title;
use SMW\Tests\PHPUnitCompat;

/**
 * @group semantic-mediawiki-integration
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.2
 *
 * @author mwjames
 */
class ResultPrinterIntegrationTest extends DatabaseTestCase {

	use PHPUnitCompat;

	private $subjects = [];
	private $pageCreator;

	private $stringBuilder;
	private $stringValidator;

	protected function setUp() : void {
		parent::setUp();

		$utilityFactory = UtilityFactory::getInstance();

		$this->pageCreator = $utilityFactory->newPageCreator();
		$this->stringBuilder = $utilityFactory->newStringBuilder();
		$this->stringValidator = $utilityFactory->newValidatorFactory()->newStringValidator();
	}

	protected function tearDown() : void {
		UtilityFactory::getInstance()->newPageDeleter()->doDeletePoolOfPages( $this->subjects );
		parent::tearDown();
	}

	/**
	 * @see #755
	 * @query {{#ask: [[Modification date::+]]|limit=0|searchlabel= }}
	 */
	public function testLimitNullWithEmptySearchlabel() {

		foreach ( [ 'Foo', 'Bar', 'テスト' ] as $title ) {

			$this->pageCreator
				->createPage( Title::newFromText( $title ) )
				->doEdit( '[[Category:LimitNullForEmptySearchlabel]]');

			$this->subjects[] = $this->pageCreator->getPage();
		}

		$this->stringBuilder
			->addString( '{{#ask:' )
			->addString( '[[Modification date::+]][[Category:LimitNullForEmptySearchlabel]]' )
			->addString( '|limit=0' )
			->addString( '|searchlabel=' )
			->addString( '}}' );

		$this->pageCreator
			->createPage( Title::newFromText( __METHOD__ ) )
			->doEdit( $this->stringBuilder->getString() );

		$this->subjects[] = $this->pageCreator->getPage();

		$parserOutput = $this->pageCreator->getEditInfo()->getOutput();

		$this->assertNotContains(
			'[[Special:Ask/-5B-5BModification-20date::+-5D-5D-5B-5BCategory:LimitNullForEmptySearchlabel-5D-5D/searchlabel=/offset=0|]]',
			$parserOutput->getText()
		);
	}

	/**
	 * @see #755
	 * @query {{#ask: [[Modification date::+]]|limit=0|searchlabel=do something }}
	 */
	public function testLimitNullWithDescriptiveSearchlabel() {

		foreach ( [ 'Foo', 'Bar', 'テスト' ] as $title ) {

			$this->pageCreator
				->createPage( Title::newFromText( $title ) )
				->doEdit( '[[Category:LimitNullForNotEmptySearchlabel]]');

			$this->subjects[] = $this->pageCreator->getPage();
		}

		$this->stringBuilder
			->addString( '{{#ask:' )
			->addString( '[[Modification date::+]][[Category:LimitNullForNotEmptySearchlabel]]' )
			->addString( '|limit=0' )
			->addString( '|searchlabel=do something' )
			->addString( '}}' );

		$this->pageCreator
			->createPage( Title::newFromText( __METHOD__ ) )
			->doEdit( $this->stringBuilder->getString() );

		$this->subjects[] = $this->pageCreator->getPage();

		$parserOutput = $this->pageCreator->getEditInfo()->getOutput();

		$this->assertContains(
			'do something',
			$parserOutput->getText()
		);
	}

}
